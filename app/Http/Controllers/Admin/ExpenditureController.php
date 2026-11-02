<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Expenditure;
use App\Models\ExpenditureRollout;
use App\Models\Investment;
use App\Models\JournalEntry;
use App\Models\StaffRemunerationCalculation;
use App\Models\SystemAccount;
use App\Models\User;
use App\Services\MobileMoneyService;
use App\Services\StaffRemuneration\WeeklyRemunerationWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ExpenditureController extends Controller
{
    private const MOBILE_MONEY_METHOD = 'Mobile Money';

    public function __construct(private readonly MobileMoneyService $mobileMoneyService)
    {
    }

    public function index(Request $request)
    {
        $query = Expenditure::with(['expenseAccount', 'paymentAccount', 'branch', 'assignedUser', 'requestedBy'])
            ->latest('expense_date')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('expense_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $expenditures = $query->paginate((int) $request->get('per_page', 20))->withQueryString();
        $branches = Branch::active()->orderBy('name')->get(['id', 'name']);
        $stats = $this->expenseStats($request);

        return view('admin.expenditures.index', compact('expenditures', 'branches', 'stats'));
    }

    public function create()
    {
        return view('admin.expenditures.create', $this->formOptions());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:operational,allowance,other'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'expense_account_id' => ['required', 'integer', 'exists:system_accounts,Id'],
            'payment_account_id' => ['nullable', 'integer', 'exists:system_accounts,Id'],
            'investment_id' => ['nullable', 'integer', 'exists:investment,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:expense_date'],
            'payment_method' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['expense_number'] = Expenditure::nextExpenseNumber();
        $validated['requested_by'] = $request->user()?->id;
        $validated['status'] = Expenditure::STATUS_PENDING;
        $validated['payment_method'] = self::MOBILE_MONEY_METHOD;
        $validated['payment_channel'] = 'mobile_money';

        $expense = Expenditure::create($validated);

        return redirect()
            ->route('admin.expenditures.show', $expense)
            ->with('success', 'Expenditure request recorded and is pending approval.');
    }

    public function show(Expenditure $expenditure)
    {
        $expenditure->load([
            'expenseAccount',
            'paymentAccount',
            'investment',
            'branch',
            'requestedBy',
            'assignedUser',
            'approvedBy',
            'paidBy',
            'journalEntry.lines',
            'rollout',
        ]);

        $staffPaymentNotes = $this->parseStaffPaymentNotes((string) $expenditure->notes);

        return view('admin.expenditures.show', array_merge(
            compact('expenditure', 'staffPaymentNotes'),
            $this->formOptions()
        ));
    }

    public function approve(Request $request, Expenditure $expenditure)
    {
        if (!$expenditure->canBeApproved()) {
            return back()->with('error', 'Only expenditures pending approval can be approved.');
        }

        if ($this->isSameUser($request, $expenditure->requested_by)) {
            return back()->with('error', 'Maker-checker control: the requester cannot approve this expenditure.');
        }

        DB::transaction(function () use ($request, $expenditure): void {
            StaffRemunerationCalculation::where('expenditure_id', $expenditure->id)->lockForUpdate()->first();
            $locked = Expenditure::whereKey($expenditure->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->canBeApproved(), 409, 'This expenditure is no longer pending approval.');
            app(WeeklyRemunerationWorkflowService::class)->assertPayoutMatchesCalculation($locked);
            $locked->update(['status' => Expenditure::STATUS_APPROVED, 'approved_by' => $request->user()?->id, 'approved_at' => now()]);
            $this->syncRemunerationCalculationStatus($locked, StaffRemunerationCalculation::STATUS_APPROVED, $request->user()?->id);
        });

        return back()->with('success', 'Expenditure approved and is now pending payment.');
    }

    public function reject(Request $request, Expenditure $expenditure)
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        if (!$expenditure->canBeRejected()) {
            return back()->with('error', 'Only unpaid expenditures that are not already processing can be rejected.');
        }

        DB::transaction(function () use ($request, $expenditure, $validated): void {
            StaffRemunerationCalculation::where('expenditure_id', $expenditure->id)->lockForUpdate()->first();
            $locked = Expenditure::whereKey($expenditure->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->canBeRejected(), 409, 'This expenditure can no longer be rejected.');
            $locked->update(['status' => Expenditure::STATUS_REJECTED, 'rejection_reason' => $validated['rejection_reason']]);
            $this->syncRemunerationCalculationStatus($locked, StaffRemunerationCalculation::STATUS_REVIEW, $request->user()?->id);
        });

        return redirect()->route('admin.expenditures.index')->with('success', 'Expenditure rejected.');
    }

    public function pay(Request $request, Expenditure $expenditure)
    {
        if ($expenditure->status === Expenditure::STATUS_PAID) {
            return back()->with('error', 'This expenditure has already been paid.');
        }

        if ($expenditure->status === Expenditure::STATUS_PAYMENT_PENDING) {
            return back()->with('error', 'This expenditure already has a pending mobile-money payment. Check the payment status before retrying.');
        }

        if ($expenditure->status === Expenditure::STATUS_PENDING) {
            return back()->with('error', 'Approve this expenditure before sending mobile-money payment.');
        }

        if (!$expenditure->canBePaid()) {
            return back()->with('error', 'This expenditure cannot be paid in its current status.');
        }

        if ($this->isSameUser($request, $expenditure->requested_by)) {
            return back()->with('error', 'Maker-checker control: the requester cannot pay this expenditure.');
        }

        if ($this->isSameUser($request, $expenditure->approved_by)) {
            return back()->with('error', 'Maker-checker control: the approver cannot also pay this expenditure.');
        }

        app(WeeklyRemunerationWorkflowService::class)->assertPayoutMatchesCalculation($expenditure, true);

        $request->merge([
            'payment_channel' => 'mobile_money',
            'payment_method' => self::MOBILE_MONEY_METHOD,
        ]);

        $validated = $request->validate([
            'payment_channel' => ['required', 'in:mobile_money'],
            'payment_account_id' => ['required', 'integer', 'exists:system_accounts,Id'],
            'investment_id' => ['required', 'integer', 'exists:investment,id'],
            'payment_method' => ['nullable', 'string', 'max:60'],
            'mobile_money_phone' => ['required', 'string', 'max:20'],
            'mobile_money_network' => ['nullable', 'in:MTN,AIRTEL'],
        ]);

        $paymentAccount = SystemAccount::active()->cashBank()->find($validated['payment_account_id']);
        if (!$paymentAccount) {
            return back()->with('error', 'Please select an active cash/bank payment account.');
        }

        $investment = Investment::find($validated['investment_id']);
        if (!$investment) {
            return back()->withInput()->with('error', 'Please select a valid investment funding account.');
        }

        if ((float) $investment->amount < (float) $expenditure->amount) {
            return back()->withInput()->with('error', 'Insufficient funds in selected investment account. Required: UGX '
                . number_format((float) $expenditure->amount, 0)
                . ', available: UGX ' . number_format((float) $investment->amount, 0) . '.');
        }

        return $this->initiateMobileMoneyPayment($request, $expenditure, $validated);
    }

    public function checkMobileMoneyStatus(Request $request, Expenditure $expenditure)
    {
        if (in_array($expenditure->status, [Expenditure::STATUS_REJECTED, Expenditure::STATUS_CANCELLED], true)) {
            return back()->with('error', 'This request is rejected or cancelled. Reconcile any old provider reference through accounting; it cannot be revived as a payment.');
        }
        if (!$expenditure->mobile_money_reference) {
            return back()->with('error', 'No mobile-money reference is recorded for this expenditure.');
        }

        if ($expenditure->status === Expenditure::STATUS_PAID) {
            return back()->with('info', 'This expenditure is already paid.');
        }

        app(WeeklyRemunerationWorkflowService::class)->assertPayoutMatchesCalculation($expenditure, true);
        $result = $this->mobileMoneyService->checkTransactionStatus(
            $expenditure->mobile_money_reference,
            $expenditure->mobile_money_network
        );

        $gatewayStatus = $result['status'] ?? null;

        if (!($result['success'] ?? false) || !in_array($gatewayStatus, ['completed', 'failed', 'pending'], true)) {
            DB::transaction(function () use ($expenditure, $result) {
                $expenditure->mobile_money_message = $result['message'] ?? 'Unable to confirm mobile-money status.';
                $expenditure->mobile_money_raw = json_encode($result);
                $expenditure->save();
            });

            return back()->with('error', $result['message'] ?? 'Unable to confirm mobile-money status. Local payment state was not changed.');
        }

        try {
            DB::transaction(function () use ($request, $expenditure, $result, $gatewayStatus) {
                StaffRemunerationCalculation::where('expenditure_id', $expenditure->id)->lockForUpdate()->first();
                $expenditure = Expenditure::whereKey($expenditure->id)->lockForUpdate()->firstOrFail();

                if ($expenditure->status === Expenditure::STATUS_PAID) {
                    return;
                }
                if (in_array($expenditure->status, [Expenditure::STATUS_REJECTED, Expenditure::STATUS_CANCELLED], true)) {
                    throw new \RuntimeException('The request was rejected or cancelled while its status was being checked. Reconcile the provider response through accounting.');
                }
                app(WeeklyRemunerationWorkflowService::class)->assertPayoutMatchesCalculation($expenditure, true);

                $expenditure->mobile_money_status = $gatewayStatus;
                $expenditure->mobile_money_message = $result['message'] ?? null;
                $expenditure->mobile_money_raw = json_encode($result);

                if ($gatewayStatus === 'completed') {
                    $this->markExpenditurePaid($expenditure, $request->user()?->id);
                } elseif ($gatewayStatus === 'failed') {
                    $this->releaseInvestmentDebit($expenditure);
                    $expenditure->status = Expenditure::STATUS_PAYMENT_FAILED;
                    $expenditure->save();
                    $this->syncRemunerationCalculationStatus(
                        $expenditure,
                        StaffRemunerationCalculation::STATUS_PAYMENT_FAILED,
                        $request->user()?->id
                    );
                } else {
                    $expenditure->status = Expenditure::STATUS_PAYMENT_PENDING;
                    $expenditure->save();
                    $this->syncRemunerationCalculationStatus(
                        $expenditure,
                        StaffRemunerationCalculation::STATUS_PAYMENT_PENDING,
                        $request->user()?->id
                    );
                }

                $this->updateRawPaymentTrace($expenditure, $gatewayStatus, $result);
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($expenditure->rollout_batch_id) {
            $this->refreshRolloutPaymentStatus((int) $expenditure->rollout_batch_id, $request->user()?->id);
        }

        return back()->with(
            $gatewayStatus === 'completed' ? 'success' : ($gatewayStatus === 'failed' ? 'error' : 'info'),
            $gatewayStatus === 'completed'
                ? 'Mobile-money payment confirmed and posted to the general ledger.'
                : ($result['message'] ?? 'Mobile-money payment is still pending.')
        );
    }

    /** Compatibility link only; current payroll lives in remuneration review. */
    public function rollout(Request $request)
    {
        return redirect()->route('admin.expenditures.remuneration', $request->only('period_start', 'officer_id'));
    }

    public function generateRollout(Request $request): never
    {
        $this->legacyStaffPaymentRolloutRetired();
    }

    public function generateIndividualPayout(Request $request): never
    {
        $this->legacyStaffPaymentRolloutRetired();
    }

    public function showRollout(ExpenditureRollout $rollout)
    {
        $this->ensureStaffPaymentRolloutAccess(request());
        $rollout->load(['items.user', 'items.expenditure', 'expenseAccount', 'paymentAccount', 'investment', 'branch', 'generatedBy']);
        // Historical pages display only the stored basis, never a current or reconstructed formula.
        $policy = $rollout->basis ?? [];
        return view('admin.expenditures.rollout-show', compact('rollout', 'policy'));
    }

    public function approveRollout(Request $request, ExpenditureRollout $rollout): never
    {
        $this->legacyStaffPaymentRolloutRetired();
    }

    public function payRollout(Request $request, ExpenditureRollout $rollout): never
    {
        $this->legacyStaffPaymentRolloutRetired();
    }

    private function formOptions(): array
    {
        return [
            'expenseAccounts' => SystemAccount::active()
                ->where('category', 'Expense')
                ->orderBy('code')
                ->orderBy('sub_code')
                ->get(),
            'paymentAccounts' => SystemAccount::active()
                ->cashBank()
                ->orderBy('code')
                ->orderBy('sub_code')
                ->get(),
            'investments' => Investment::orderBy('name')->get(),
            'branches' => Branch::active()->orderBy('name')->get(['id', 'name']),
            'users' => User::where('status', 'active')->orderBy('name')->get(['id', 'name', 'branch_id', 'designation']),
        ];
    }

    private function initiateMobileMoneyPayment(Request $request, Expenditure $expenditure, array $validated)
    {
        $result = $this->processExpenditureMobileMoneyPayment(
            $request,
            $expenditure,
            $validated,
            (string) $validated['mobile_money_phone']
        );

        if ($result['success']) {
            return back()->with(
                $result['completed'] ? 'success' : 'info',
                $result['completed']
                    ? 'Mobile-money payment completed and posted to the general ledger.'
                    : 'Mobile-money payment initiated. Check status before treating it as paid.'
            );
        }

        return back()->withInput()->with('error', $result['message'] ?? 'Mobile-money payment failed to initiate.');
    }

    private function processExpenditureMobileMoneyPayment(Request $request, Expenditure $expenditure, array $validated, string $phone): array
    {
        $phoneValidation = $this->mobileMoneyService->validatePhoneNumber($phone);
        if (!$phoneValidation['valid']) {
            $expenditure->update([
                'status' => Expenditure::STATUS_PAYMENT_FAILED,
                'mobile_money_message' => $phoneValidation['message'],
            ]);
            $this->syncRemunerationCalculationStatus(
                $expenditure,
                StaffRemunerationCalculation::STATUS_PAYMENT_FAILED,
                $request->user()?->id
            );

            return [
                'success' => false,
                'completed' => false,
                'message' => $phoneValidation['message'],
            ];
        }

        $formattedPhone = $phoneValidation['formatted_phone'];
        $network = ($validated['mobile_money_network'] ?? null) ?: ($phoneValidation['network'] ?? null);

        try {
            DB::transaction(function () use ($request, $expenditure, $validated, $formattedPhone, $network) {
                StaffRemunerationCalculation::where('expenditure_id', $expenditure->id)->lockForUpdate()->first();
                $lockedExpenditure = Expenditure::whereKey($expenditure->id)->lockForUpdate()->firstOrFail();

                if (!$lockedExpenditure->canBePaid()) {
                    throw new \RuntimeException('This expenditure cannot be paid in its current status.');
                }
                app(WeeklyRemunerationWorkflowService::class)->assertPayoutMatchesCalculation($lockedExpenditure, true);

                $lockedExpenditure->payment_account_id = $validated['payment_account_id'];
                $lockedExpenditure->investment_id = $validated['investment_id'];
                $lockedExpenditure->payment_method = self::MOBILE_MONEY_METHOD;
                $lockedExpenditure->payment_channel = 'mobile_money';
                $lockedExpenditure->mobile_money_phone = $formattedPhone;
                $lockedExpenditure->mobile_money_network = $network;
                $lockedExpenditure->mobile_money_status = 'pending';
                $lockedExpenditure->payment_initiated_at = now();

                if ($lockedExpenditure->status === Expenditure::STATUS_APPROVED && !$lockedExpenditure->approved_at) {
                    $lockedExpenditure->approved_by = $request->user()?->id;
                    $lockedExpenditure->approved_at = now();
                }

                $this->debitInvestmentForExpenditure($lockedExpenditure);
                $lockedExpenditure->status = Expenditure::STATUS_PAYMENT_PENDING;
                $lockedExpenditure->save();

                $expenditure->setRawAttributes($lockedExpenditure->getAttributes(), true);
                $expenditure->syncOriginal();
            });
        } catch (\Throwable $e) {
            $currentStatus = Expenditure::whereKey($expenditure->id)->value('status');
            if (in_array($currentStatus, [Expenditure::STATUS_APPROVED, Expenditure::STATUS_PAYMENT_FAILED], true)) {
                $expenditure->update([
                    'status' => Expenditure::STATUS_PAYMENT_FAILED,
                    'mobile_money_message' => $e->getMessage(),
                ]);
                $this->syncRemunerationCalculationStatus($expenditure, StaffRemunerationCalculation::STATUS_PAYMENT_FAILED, $request->user()?->id);
            }

            return [
                'success' => false,
                'completed' => false,
                'message' => $e->getMessage(),
            ];
        }

        $expenditure->loadMissing('assignedUser');
        $recipientName = $expenditure->assignedUser->name ?? $expenditure->title;

        try {
            $result = $this->mobileMoneyService->disburse(
                $formattedPhone,
                (float) $expenditure->amount,
                $network,
                $recipientName,
                $this->mobileMoneyRequestId()
            );
        } catch (\Throwable $e) {
            Log::error('Expenditure mobile-money disbursement exception', [
                'expenditure_id' => $expenditure->id,
                'error' => $e->getMessage(),
            ]);

            DB::transaction(function () use ($expenditure, $e, $request) {
                StaffRemunerationCalculation::where('expenditure_id', $expenditure->id)->lockForUpdate()->first();
                $expenditure = Expenditure::whereKey($expenditure->id)->lockForUpdate()->firstOrFail();
                $this->releaseInvestmentDebit($expenditure);
                $expenditure->status = Expenditure::STATUS_PAYMENT_FAILED;
                $expenditure->mobile_money_status = 'exception';
                $expenditure->mobile_money_message = 'Disbursement failed before provider reference was returned: ' . $e->getMessage();
                $expenditure->mobile_money_raw = json_encode([
                    'success' => false,
                    'status_code' => 'EXCEPTION',
                    'message' => $e->getMessage(),
                ]);
                $expenditure->save();
                $this->syncRemunerationCalculationStatus(
                    $expenditure,
                    StaffRemunerationCalculation::STATUS_PAYMENT_FAILED,
                    $request->user()?->id
                );
            });

            return [
                'success' => false,
                'completed' => false,
                'message' => 'Disbursement failed: ' . $e->getMessage(),
            ];
        }

        $reference = $result['reference']
            ?? $result['transaction_reference']
            ?? $result['request_id']
            ?? ('EXP-' . $expenditure->id . '-' . time());

        DB::transaction(function () use ($request, $expenditure, $formattedPhone, $network, $result, $reference) {
            StaffRemunerationCalculation::where('expenditure_id', $expenditure->id)->lockForUpdate()->first();
            $expenditure = Expenditure::whereKey($expenditure->id)->lockForUpdate()->firstOrFail();
            $expenditure->mobile_money_reference = $reference;
            $expenditure->mobile_money_status = $result['status_code'] ?? ($result['status'] ?? 'pending');
            $expenditure->mobile_money_message = $result['message'] ?? null;
            $expenditure->mobile_money_raw = json_encode($result);
            $expenditure->payment_initiated_at = now();

            $immediateSuccess = ($result['status_code'] ?? null) === '00';

            if (($result['success'] ?? false) && $immediateSuccess) {
                $this->markExpenditurePaid($expenditure, $request->user()?->id);
            } elseif ($result['success'] ?? false) {
                $expenditure->status = Expenditure::STATUS_PAYMENT_PENDING;
                $expenditure->save();
                $this->syncRemunerationCalculationStatus(
                    $expenditure,
                    StaffRemunerationCalculation::STATUS_PAYMENT_PENDING,
                    $request->user()?->id
                );
            } else {
                $this->releaseInvestmentDebit($expenditure);
                $expenditure->status = Expenditure::STATUS_PAYMENT_FAILED;
                $expenditure->save();
                $this->syncRemunerationCalculationStatus(
                    $expenditure,
                    StaffRemunerationCalculation::STATUS_PAYMENT_FAILED,
                    $request->user()?->id
                );
            }

            $this->createRawPaymentTrace(
                $expenditure,
                $reference,
                $formattedPhone,
                $network,
                ($result['success'] ?? false) ? ($immediateSuccess ? '01' : '00') : '02',
                $result,
                $request->user()?->id
            );
        });

        return [
            'success' => (bool) ($result['success'] ?? false),
            'completed' => (bool) (($result['success'] ?? false) && (($result['status_code'] ?? null) === '00')),
            'message' => $result['message'] ?? null,
            'reference' => $reference,
        ];
    }

    private function expenseStats(Request $request): array
    {
        $base = Expenditure::query();

        if ($request->filled('date_from')) {
            $base->whereDate('expense_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $base->whereDate('expense_date', '<=', $request->date_to);
        }

        return [
            'pending_approval' => (clone $base)->where('status', Expenditure::STATUS_PENDING)->sum('amount'),
            'pending_payment' => (clone $base)->whereIn('status', [
                Expenditure::STATUS_APPROVED,
                Expenditure::STATUS_PAYMENT_PENDING,
                Expenditure::STATUS_PAYMENT_FAILED,
            ])->sum('amount'),
            'paid' => (clone $base)->where('status', Expenditure::STATUS_PAID)->sum('amount'),
            'count' => (clone $base)->count(),
        ];
    }

    private function parseStaffPaymentNotes(string $notes): array
    {
        $marker = 'Staff payment basis:';
        if (!str_contains($notes, $marker)) {
            return [
                'note' => trim($notes),
                'basis' => [],
            ];
        }

        [$note, $basisJson] = explode($marker, $notes, 2);
        $decoded = json_decode(trim($basisJson), true);

        return [
            'note' => trim($note),
            'basis' => is_array($decoded) ? $decoded : [],
        ];
    }

    private function markExpenditurePaid(Expenditure $expenditure, ?int $userId): void
    {
        if (!$expenditure->journal_entry_id) {
            $alreadyPosted = JournalEntry::where('reference_type', 'Expenditure')
                ->where('reference_id', $expenditure->id)
                ->where('status', '!=', 'reversed')
                ->exists();

            $journal = $this->postExpenditureJournal($expenditure, $userId);
            $expenditure->journal_entry_id = $journal->Id;

            if (!$alreadyPosted) {
                $this->debitInvestmentForExpenditure($expenditure);
            }
        }

        if (!$expenditure->approved_at) {
            $expenditure->approved_by = $expenditure->approved_by ?: $userId;
            $expenditure->approved_at = now();
        }

        $expenditure->status = Expenditure::STATUS_PAID;
        $expenditure->paid_at = now();
        $expenditure->paid_by = $userId;
        $expenditure->save();
        $this->syncRemunerationCalculationStatus(
            $expenditure,
            StaffRemunerationCalculation::STATUS_PAID,
            $userId
        );
    }

    private function syncRemunerationCalculationStatus(
        Expenditure $expenditure,
        string $status,
        ?int $userId = null
    ): void {
        if (!Schema::hasTable('staff_remuneration_weekly_calculations')) {
            return;
        }

        $calculation = StaffRemunerationCalculation::where('expenditure_id', $expenditure->id)->lockForUpdate()->first();
        if (!$calculation) {
            return;
        }

        $updates = ['status' => $status];

        if ($status === StaffRemunerationCalculation::STATUS_APPROVED) {
            $updates['approved_by'] = $userId;
            $updates['approved_at'] = now();
        } elseif ($status === StaffRemunerationCalculation::STATUS_PAID) {
            $updates['paid_by'] = $userId;
            $updates['paid_at'] = now();
        }

        $updates['audit_history'] = [...($calculation->audit_history ?? []), [
            'event' => $status, 'by' => $userId, 'at' => now()->toIso8601String(),
            'expenditure_id' => $expenditure->id,
        ]];
        $calculation->update($updates);
    }

    private function debitInvestmentForExpenditure(Expenditure $expenditure): void
    {
        if (!$expenditure->investment_id || $expenditure->investment_debited_at) {
            return;
        }

        $updated = Investment::where('id', $expenditure->investment_id)
            // Legacy investment.amount is VARCHAR: compare money numerically, not lexically.
            ->whereRaw('CAST(amount AS DECIMAL(20, 2)) >= ?', [(float) $expenditure->amount])
            ->decrement('amount', (float) $expenditure->amount);

        if ($updated < 1) {
            $investment = Investment::find($expenditure->investment_id);
            $available = $investment ? (float) $investment->amount : 0.0;

            throw new \RuntimeException('Insufficient funds in selected investment account. Required: UGX '
                . number_format((float) $expenditure->amount, 0)
                . ', available: UGX ' . number_format($available, 0) . '.');
        }

        $expenditure->investment_debited_at = now();
    }

    private function releaseInvestmentDebit(Expenditure $expenditure): void
    {
        if (!$expenditure->investment_id || !$expenditure->investment_debited_at) {
            return;
        }

        Investment::where('id', $expenditure->investment_id)
            ->increment('amount', (float) $expenditure->amount);

        $expenditure->investment_debited_at = null;
    }

    private function refreshRolloutPaymentStatus(int $rolloutId, ?int $userId): void
    {
        $rollout = ExpenditureRollout::with('items.expenditure')->find($rolloutId);
        if (!$rollout) {
            return;
        }

        $statuses = $rollout->items
            ->filter(fn ($item) => (float) $item->payout_amount > 0)
            ->map(fn ($item) => $item->expenditure?->status)
            ->filter()
            ->values();

        if ($statuses->isEmpty()) {
            return;
        }

        if ($statuses->every(fn ($status) => $status === 'paid')) {
            $rollout->status = 'paid';
            $rollout->paid_by = $rollout->paid_by ?: $userId;
            $rollout->paid_at = $rollout->paid_at ?: now();
        } elseif ($statuses->contains('payment_pending')) {
            $rollout->status = 'payment_pending';
            $rollout->paid_by = null;
            $rollout->paid_at = null;
        } elseif ($statuses->contains('payment_failed')) {
            $rollout->status = 'payment_failed';
            $rollout->paid_by = null;
            $rollout->paid_at = null;
        }

        $rollout->save();
    }

    private function postExpenditureJournal(Expenditure $expenditure, ?int $userId): JournalEntry
    {
        $expenditure->loadMissing(['expenseAccount', 'paymentAccount', 'investment']);

        $existingJournal = JournalEntry::where('reference_type', 'Expenditure')
            ->where('reference_id', $expenditure->id)
            ->where('status', '!=', 'reversed')
            ->orderByDesc('Id')
            ->first();

        if ($existingJournal) {
            return $existingJournal;
        }

        if (!$expenditure->expenseAccount || $expenditure->expenseAccount->category !== 'Expense') {
            throw new \RuntimeException('The selected expense account is invalid.');
        }

        if (!$expenditure->paymentAccount || !$expenditure->paymentAccount->is_cash_bank) {
            throw new \RuntimeException('The selected payment account must be a cash/bank account.');
        }

        return JournalEntry::postJournal([
            'transaction_date' => now()->toDateString(),
            'reference_type' => 'Expenditure',
            'reference_id' => $expenditure->id,
            'cost_center_id' => $expenditure->branch_id,
            'officer_id' => $expenditure->assigned_user_id,
            'narrative' => $expenditure->expense_number . ' - ' . $expenditure->title,
            'posted_by' => $userId,
            'inv_id' => $expenditure->investment_id,
        ], [
            [
                'account_id' => $expenditure->expense_account_id,
                'debit' => (float) $expenditure->amount,
                'credit' => 0,
                'narrative' => 'Recognize expenditure: ' . $expenditure->title,
            ],
            [
                'account_id' => $expenditure->payment_account_id,
                'debit' => 0,
                'credit' => (float) $expenditure->amount,
                'narrative' => 'Payment for expenditure: ' . $expenditure->title
                    . ($expenditure->investment ? ' from ' . $expenditure->investment->name : ''),
            ],
        ]);
    }

    private function createRawPaymentTrace(
        Expenditure $expenditure,
        string $reference,
        string $phone,
        ?string $network,
        string $status,
        array $result,
        ?int $userId
    ): void {
        if (!Schema::hasTable('raw_payments')) {
            return;
        }

        $columns = array_flip(Schema::getColumnListing('raw_payments'));
        $data = [
            'phone' => $phone,
            'amount' => (string) $expenditure->amount,
            'trans_id' => $reference,
            'txn_id' => $reference,
            'ref' => $reference,
            'message' => $result['message'] ?? 'Expenditure mobile-money payment',
            'status' => $status,
            'pay_status' => $expenditure->status,
            'pay_message' => $result['message'] ?? null,
            'pay_date' => now(),
            'type' => 'expenditure',
            'added_by' => $userId,
            'direction' => 'outgoing',
            'raw_message' => json_encode($result + ['network' => $network]),
            'expenditure_id' => $expenditure->id,
            'network' => $network,
            'date_created' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $data = array_intersect_key($data, $columns);

        try {
            DB::table('raw_payments')->insert($data);
        } catch (\Throwable $e) {
            Log::warning('Could not create expenditure raw payment record', [
                'expenditure_id' => $expenditure->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function updateRawPaymentTrace(Expenditure $expenditure, string $gatewayStatus, array $result): void
    {
        if (!Schema::hasTable('raw_payments') || !Schema::hasColumn('raw_payments', 'expenditure_id')) {
            return;
        }

        $columns = array_flip(Schema::getColumnListing('raw_payments'));
        $data = [
            'status' => $gatewayStatus === 'completed' ? '01' : ($gatewayStatus === 'failed' ? '02' : '00'),
            'pay_status' => $gatewayStatus,
            'pay_message' => $result['message'] ?? null,
            'pay_date' => now(),
            'raw_message' => json_encode($result),
            'updated_at' => now(),
        ];
        $data = array_intersect_key($data, $columns);

        DB::table('raw_payments')
            ->where('expenditure_id', $expenditure->id)
            ->when(Schema::hasColumn('raw_payments', 'txn_id'), fn ($query) => $query->where('txn_id', $expenditure->mobile_money_reference))
            ->when(!Schema::hasColumn('raw_payments', 'txn_id') && Schema::hasColumn('raw_payments', 'ref'), fn ($query) => $query->where('ref', $expenditure->mobile_money_reference))
            ->update($data);
    }

    private function ensureStaffPaymentRolloutAccess(Request $request): void
    {
        if (!$request->user()?->canManageStaffPaymentRollout()) {
            abort(403, 'Access denied. Only the Super Administrator or Administrator can manage staff payment rollout.');
        }
    }

    private function legacyStaffPaymentRolloutRetired(): never
    {
        abort(410, 'The legacy staff payroll calculation has been retired. No new staff payout can be generated from the old rollout.');
    }

    private function isSameUser(Request $request, mixed $userId): bool
    {
        return $userId !== null && (int) $request->user()?->id === (int) $userId;
    }

    private function mobileMoneyRequestId(): string
    {
        return 'EbP'
            . substr((string) time(), -6)
            . substr(microtime(false), 2, 3)
            . str_pad((string) mt_rand(0, 99), 2, '0', STR_PAD_LEFT);
    }

}
