<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Expenditure;
use App\Models\ExpenditureRollout;
use App\Models\ExpenditureRolloutItem;
use App\Models\Investment;
use App\Models\JournalEntry;
use App\Models\PersonalLoan;
use App\Models\RawPayment;
use App\Models\SystemAccount;
use App\Models\User;
use App\Services\MobileMoneyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            'type' => ['required', 'in:operational,performance_payout,allowance,other'],
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

        $validated['expense_number'] = $this->nextExpenseNumber();
        $validated['requested_by'] = $request->user()?->id;
        $validated['status'] = 'pending';
        $validated['payment_method'] = self::MOBILE_MONEY_METHOD;
        $validated['payment_channel'] = 'mobile_money';

        $expense = Expenditure::create($validated);

        return redirect()
            ->route('admin.expenditures.show', $expense)
            ->with('success', 'Expenditure recorded and is pending approval.');
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

        return view('admin.expenditures.show', array_merge(
            compact('expenditure'),
            $this->formOptions()
        ));
    }

    public function approve(Request $request, Expenditure $expenditure)
    {
        if (in_array($expenditure->status, ['paid', 'cancelled', 'rejected'], true)) {
            return back()->with('error', 'This expenditure cannot be approved in its current status.');
        }

        $expenditure->update([
            'status' => 'approved',
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Expenditure approved.');
    }

    public function reject(Request $request, Expenditure $expenditure)
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($expenditure->status === 'paid') {
            return back()->with('error', 'A paid expenditure cannot be rejected.');
        }

        $expenditure->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return redirect()->route('admin.expenditures.index')->with('success', 'Expenditure rejected.');
    }

    public function pay(Request $request, Expenditure $expenditure)
    {
        if ($expenditure->status === 'paid') {
            return back()->with('error', 'This expenditure has already been paid.');
        }

        if ($expenditure->status === 'payment_pending') {
            return back()->with('error', 'This expenditure already has a pending mobile-money payment. Check the payment status before retrying.');
        }

        if (in_array($expenditure->status, ['rejected', 'cancelled'], true)) {
            return back()->with('error', 'This expenditure cannot be paid in its current status.');
        }

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
        if (!$expenditure->mobile_money_reference) {
            return back()->with('error', 'No mobile-money reference is recorded for this expenditure.');
        }

        if ($expenditure->status === 'paid') {
            return back()->with('info', 'This expenditure is already paid.');
        }

        $result = $this->mobileMoneyService->checkTransactionStatus(
            $expenditure->mobile_money_reference,
            $expenditure->mobile_money_network
        );

        $gatewayStatus = $result['status'] ?? 'pending';

        DB::transaction(function () use ($request, $expenditure, $result, $gatewayStatus) {
            $expenditure->mobile_money_status = $gatewayStatus;
            $expenditure->mobile_money_message = $result['message'] ?? null;
            $expenditure->mobile_money_raw = json_encode($result);

            if ($gatewayStatus === 'completed') {
                $this->markExpenditurePaid($expenditure, $request->user()?->id);
            } elseif ($gatewayStatus === 'failed') {
                $expenditure->status = 'payment_failed';
                $expenditure->save();
            } else {
                $expenditure->status = 'payment_pending';
                $expenditure->save();
            }

            $this->updateRawPaymentTrace($expenditure, $gatewayStatus, $result);
        });

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

    public function rollout(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $defaults = [
            'period_start' => $request->get('period_start', now()->startOfMonth()->toDateString()),
            'period_end' => $request->get('period_end', now()->toDateString()),
            'branch_id' => $request->get('branch_id'),
            'per_assigned_loan' => (float) $request->get('per_assigned_loan', 0),
            'per_performing_loan' => (float) $request->get('per_performing_loan', 0),
            'per_follow_up' => (float) $request->get('per_follow_up', 0),
            'collection_commission_percent' => (float) $request->get('collection_commission_percent', 0),
        ];

        $rows = $this->performanceRows($defaults);
        $rollouts = ExpenditureRollout::with(['branch', 'generatedBy'])
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.expenditures.rollout', array_merge(
            $this->formOptions(),
            compact('defaults', 'rows', 'rollouts')
        ));
    }

    public function generateRollout(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'expense_account_id' => ['required', 'integer', 'exists:system_accounts,Id'],
            'payment_account_id' => ['nullable', 'integer', 'exists:system_accounts,Id'],
            'investment_id' => ['nullable', 'integer', 'exists:investment,id'],
            'per_assigned_loan' => ['nullable', 'numeric', 'min:0'],
            'per_performing_loan' => ['nullable', 'numeric', 'min:0'],
            'per_follow_up' => ['nullable', 'numeric', 'min:0'],
            'collection_commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $rows = $this->performanceRows($validated)->filter(fn ($row) => $row['payout_amount'] > 0)->values();
        if ($rows->isEmpty()) {
            return back()->withInput()->with('error', 'No payout amounts were generated for the selected period and formula.');
        }

        $rollout = DB::transaction(function () use ($request, $validated, $rows) {
            $rollout = ExpenditureRollout::create([
                'rollout_number' => $this->nextRolloutNumber(),
                'title' => $validated['title'],
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'branch_id' => $validated['branch_id'] ?? null,
                'expense_account_id' => $validated['expense_account_id'],
                'payment_account_id' => $validated['payment_account_id'] ?? null,
                'investment_id' => $validated['investment_id'] ?? null,
                'status' => 'draft',
                'basis' => [
                    'per_assigned_loan' => (float) ($validated['per_assigned_loan'] ?? 0),
                    'per_performing_loan' => (float) ($validated['per_performing_loan'] ?? 0),
                    'per_follow_up' => (float) ($validated['per_follow_up'] ?? 0),
                    'collection_commission_percent' => (float) ($validated['collection_commission_percent'] ?? 0),
                ],
                'total_amount' => $rows->sum('payout_amount'),
                'generated_by' => $request->user()?->id,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($rows as $row) {
                ExpenditureRolloutItem::create([
                    'rollout_id' => $rollout->id,
                    'user_id' => $row['user_id'],
                    'assigned_loans_count' => $row['assigned_loans_count'],
                    'performing_loans_count' => $row['performing_loans_count'],
                    'overdue_loans_count' => $row['overdue_loans_count'],
                    'followups_count' => $row['followups_count'],
                    'collections_amount' => $row['collections_amount'],
                    'payout_amount' => $row['payout_amount'],
                ]);
            }

            return $rollout;
        });

        return redirect()->route('admin.expenditures.rollout.show', $rollout)
            ->with('success', 'Performance payout rollout generated for approval.');
    }

    public function generateIndividualPayout(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $request->merge([
            'individual_payout_amount' => collect($request->input('individual_payout_amount', []))
                ->map(fn ($value) => is_string($value) ? str_replace(',', '', $value) : $value)
                ->all(),
        ]);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'expense_account_id' => ['required', 'integer', 'exists:system_accounts,Id'],
            'payment_account_id' => ['nullable', 'integer', 'exists:system_accounts,Id'],
            'investment_id' => ['nullable', 'integer', 'exists:investment,id'],
            'per_assigned_loan' => ['nullable', 'numeric', 'min:0'],
            'per_performing_loan' => ['nullable', 'numeric', 'min:0'],
            'per_follow_up' => ['nullable', 'numeric', 'min:0'],
            'collection_commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'individual_payout_amount' => ['nullable', 'array'],
            'individual_payout_amount.*' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $row = $this->performanceRows($validated)->firstWhere('user_id', (int) $validated['user_id']);
        if (!$row) {
            return back()->withInput()->with('error', 'This user is not available for the selected rollout filters.');
        }

        $payoutAmount = (float) data_get(
            $validated,
            'individual_payout_amount.' . $validated['user_id'],
            $row['payout_amount']
        );

        if ($payoutAmount <= 0) {
            return back()->withInput()->with('error', 'This user has no payout amount for the selected period and formula.');
        }

        $periodStart = Carbon::parse($validated['period_start'])->toDateString();
        $periodEnd = Carbon::parse($validated['period_end'])->toDateString();

        $existing = Expenditure::where('type', 'performance_payout')
            ->where('assigned_user_id', $row['user_id'])
            ->whereNotIn('status', ['rejected', 'cancelled'])
            ->where('description', 'like', "%{$periodStart}%")
            ->where('description', 'like', "%{$periodEnd}%")
            ->first();

        if ($existing) {
            return redirect()
                ->route('admin.expenditures.show', $existing)
                ->with('error', 'A performance payout already exists for this user and period.');
        }

        $expense = Expenditure::create([
            'expense_number' => $this->nextExpenseNumber(),
            'type' => 'performance_payout',
            'title' => 'Performance payout - ' . $row['user_name'],
            'description' => $validated['title'] . ' (' . $periodStart . ' to ' . $periodEnd . ')',
            'expense_account_id' => $validated['expense_account_id'],
            'payment_account_id' => $validated['payment_account_id'] ?? null,
            'investment_id' => $validated['investment_id'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'requested_by' => $request->user()?->id,
            'assigned_user_id' => $row['user_id'],
            'amount' => $payoutAmount,
            'expense_date' => now()->toDateString(),
            'status' => 'approved',
            'payment_method' => self::MOBILE_MONEY_METHOD,
            'payment_channel' => 'mobile_money',
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
            'notes' => trim(($validated['notes'] ?? '') . "\nFormula: "
                . json_encode([
                    'assigned_loans' => $row['assigned_loans_count'],
                    'performing_loans' => $row['performing_loans_count'],
                    'overdue_loans' => $row['overdue_loans_count'],
                    'followups' => $row['followups_count'],
                    'collections' => $row['collections_amount'],
                    'per_assigned_loan' => (float) ($validated['per_assigned_loan'] ?? 0),
                    'per_performing_loan' => (float) ($validated['per_performing_loan'] ?? 0),
                    'per_follow_up' => (float) ($validated['per_follow_up'] ?? 0),
                    'collection_commission_percent' => (float) ($validated['collection_commission_percent'] ?? 0),
                ])),
        ]);

        return redirect()
            ->route('admin.expenditures.show', $expense)
            ->with('success', 'Individual performance payout created. Complete the payment from this page.');
    }

    public function showRollout(ExpenditureRollout $rollout)
    {
        $this->ensureSuperAdmin(request());

        $rollout->load(['items.user', 'items.expenditure', 'expenseAccount', 'paymentAccount', 'investment', 'branch', 'generatedBy']);

        return view('admin.expenditures.rollout-show', array_merge(
            compact('rollout'),
            $this->formOptions()
        ));
    }

    public function approveRollout(Request $request, ExpenditureRollout $rollout)
    {
        $this->ensureSuperAdmin($request);

        if ($rollout->status !== 'draft') {
            return back()->with('error', 'Only draft rollouts can be approved.');
        }

        $rollout->update([
            'status' => 'approved',
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Rollout approved.');
    }

    public function payRollout(Request $request, ExpenditureRollout $rollout)
    {
        $this->ensureSuperAdmin($request);

        if ($rollout->status === 'paid') {
            return back()->with('error', 'This rollout has already been paid.');
        }

        $request->merge([
            'payment_channel' => 'mobile_money',
            'payment_method' => self::MOBILE_MONEY_METHOD,
        ]);

        $validated = $request->validate([
            'payment_channel' => ['required', 'in:mobile_money'],
            'payment_account_id' => ['required', 'integer', 'exists:system_accounts,Id'],
            'investment_id' => ['required', 'integer', 'exists:investment,id'],
            'payment_method' => ['nullable', 'string', 'max:60'],
        ]);

        $paymentAccount = SystemAccount::active()->cashBank()->find($validated['payment_account_id']);
        if (!$paymentAccount) {
            return back()->with('error', 'Please select an active cash/bank payment account.');
        }

        $investment = Investment::find($validated['investment_id']);
        if (!$investment) {
            return back()->withInput()->with('error', 'Please select a valid investment funding account.');
        }

        $remainingAmount = (float) $rollout->items()
            ->whereNull('expenditure_id')
            ->where('payout_amount', '>', 0)
            ->sum('payout_amount');

        if ((float) $investment->amount < $remainingAmount) {
            return back()->withInput()->with('error', 'Insufficient funds in selected investment account. Required: UGX '
                . number_format($remainingAmount, 0)
                . ', available: UGX ' . number_format((float) $investment->amount, 0) . '.');
        }

        $payoutItems = $rollout->items()
            ->with('user:id,name,phone')
            ->whereNull('expenditure_id')
            ->where('payout_amount', '>', 0)
            ->get();

        $missingPhones = $payoutItems
            ->filter(fn ($item) => trim((string) ($item->user->phone ?? '')) === '')
            ->map(fn ($item) => $item->user->name ?? 'User #' . $item->user_id)
            ->values();

        if ($missingPhones->isNotEmpty()) {
            return back()->withInput()->with('error', 'Mobile-money payout requires staff phone numbers for: ' . $missingPhones->implode(', '));
        }

        $invalidPhones = $payoutItems
            ->filter(function ($item) {
                $validation = $this->mobileMoneyService->validatePhoneNumber((string) ($item->user->phone ?? ''));
                return !($validation['valid'] ?? false);
            })
            ->map(fn ($item) => $item->user->name ?? 'User #' . $item->user_id)
            ->values();

        if ($invalidPhones->isNotEmpty()) {
            return back()->withInput()->with('error', 'Mobile-money payout has invalid staff phone numbers for: ' . $invalidPhones->implode(', '));
        }

        DB::transaction(function () use ($request, $rollout, $validated) {
            if ($rollout->status !== 'approved') {
                $rollout->approved_by = $request->user()?->id;
                $rollout->approved_at = now();
            }

            $rollout->payment_account_id = $validated['payment_account_id'];
            $rollout->investment_id = $validated['investment_id'];
            $rollout->status = 'payment_pending';
            $rollout->paid_by = null;
            $rollout->paid_at = null;
            $rollout->save();

            $rollout->loadMissing('items.user');

            foreach ($rollout->items as $item) {
                if ($item->expenditure_id || (float) $item->payout_amount <= 0) {
                    continue;
                }

                $expense = Expenditure::create([
                    'expense_number' => $this->nextExpenseNumber(),
                    'type' => 'performance_payout',
                    'title' => 'Performance payout - ' . ($item->user->name ?? 'User #' . $item->user_id),
                    'description' => $rollout->title . ' (' . $rollout->period_start->format('Y-m-d') . ' to ' . $rollout->period_end->format('Y-m-d') . ')',
                    'expense_account_id' => $rollout->expense_account_id,
                    'payment_account_id' => $validated['payment_account_id'],
                    'investment_id' => $validated['investment_id'],
                    'branch_id' => $rollout->branch_id,
                    'requested_by' => $rollout->generated_by,
                    'assigned_user_id' => $item->user_id,
                    'amount' => $item->payout_amount,
                    'expense_date' => now()->toDateString(),
                    'status' => 'approved',
                    'payment_method' => self::MOBILE_MONEY_METHOD,
                    'payment_channel' => 'mobile_money',
                    'approved_by' => $request->user()?->id,
                    'approved_at' => now(),
                    'rollout_batch_id' => $rollout->id,
                    'notes' => 'Auto-created from rollout ' . $rollout->rollout_number,
                ]);

                $this->processExpenditureMobileMoneyPayment(
                    $request,
                    $expense,
                    $validated,
                    (string) ($item->user->phone ?? '')
                );

                $item->update(['expenditure_id' => $expense->id]);
            }

            $hasPendingPayments = $rollout->items()
                ->whereHas('expenditure', fn ($query) => $query->where('status', 'payment_pending'))
                ->exists();
            $hasFailedPayments = $rollout->items()
                ->whereHas('expenditure', fn ($query) => $query->where('status', 'payment_failed'))
                ->exists();
            $hasUnpaidPayments = $rollout->items()
                ->whereHas('expenditure', fn ($query) => $query->whereNotIn('status', ['paid']))
                ->exists();

            $rollout->status = $hasPendingPayments
                ? 'payment_pending'
                : ($hasFailedPayments ? 'payment_failed' : 'paid');

            if (!$hasUnpaidPayments) {
                $rollout->paid_by = $request->user()?->id;
                $rollout->paid_at = now();
            }
            $rollout->save();
        });

        return back()->with('info', 'Mobile-money rollout initiated. Check each payout status before treating pending items as paid.');
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
                'status' => 'payment_failed',
                'mobile_money_message' => $phoneValidation['message'],
            ]);

            return [
                'success' => false,
                'completed' => false,
                'message' => $phoneValidation['message'],
            ];
        }

        $formattedPhone = $phoneValidation['formatted_phone'];
        $network = ($validated['mobile_money_network'] ?? null) ?: ($phoneValidation['network'] ?? null);

        $expenditure->loadMissing('assignedUser');
        $recipientName = $expenditure->assignedUser->name ?? $expenditure->title;

        $result = $this->mobileMoneyService->disburse(
            $formattedPhone,
            (float) $expenditure->amount,
            $network,
            $recipientName,
            $this->mobileMoneyRequestId()
        );

        $reference = $result['reference']
            ?? $result['transaction_reference']
            ?? $result['request_id']
            ?? ('EXP-' . $expenditure->id . '-' . time());

        DB::transaction(function () use ($request, $expenditure, $validated, $formattedPhone, $network, $result, $reference) {
            $expenditure->payment_account_id = $validated['payment_account_id'];
            $expenditure->investment_id = $validated['investment_id'];
            $expenditure->payment_method = self::MOBILE_MONEY_METHOD;
            $expenditure->payment_channel = 'mobile_money';
            $expenditure->mobile_money_phone = $formattedPhone;
            $expenditure->mobile_money_network = $network;
            $expenditure->mobile_money_reference = $reference;
            $expenditure->mobile_money_status = $result['status_code'] ?? ($result['status'] ?? 'pending');
            $expenditure->mobile_money_message = $result['message'] ?? null;
            $expenditure->mobile_money_raw = json_encode($result);
            $expenditure->payment_initiated_at = now();

            if ($expenditure->status !== 'approved') {
                $expenditure->approved_by = $request->user()?->id;
                $expenditure->approved_at = now();
            }

            $immediateSuccess = ($result['status_code'] ?? null) === '00';

            if (($result['success'] ?? false) && $immediateSuccess) {
                $this->markExpenditurePaid($expenditure, $request->user()?->id);
            } elseif ($result['success'] ?? false) {
                $expenditure->status = 'payment_pending';
                $expenditure->save();
            } else {
                $expenditure->status = 'payment_failed';
                $expenditure->save();
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
            'pending' => (clone $base)->where('status', 'pending')->sum('amount'),
            'approved' => (clone $base)->where('status', 'approved')->sum('amount'),
            'paid' => (clone $base)->where('status', 'paid')->sum('amount'),
            'count' => (clone $base)->count(),
        ];
    }

    private function performanceRows(array $input): Collection
    {
        $periodStart = Carbon::parse($input['period_start'] ?? now()->startOfMonth())->startOfDay();
        $periodEnd = Carbon::parse($input['period_end'] ?? now())->endOfDay();
        $branchId = $input['branch_id'] ?? null;
        $perAssigned = (float) ($input['per_assigned_loan'] ?? 0);
        $perPerforming = (float) ($input['per_performing_loan'] ?? 0);
        $perFollowUp = (float) ($input['per_follow_up'] ?? 0);
        $collectionPercent = (float) ($input['collection_commission_percent'] ?? 0);

        $users = User::where('status', 'active')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id', 'designation'])
            ->keyBy('id');

        $assigned = PersonalLoan::query()
            ->whereNotNull('assigned_to')
            ->whereIn('status', [2, 3])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->groupBy('assigned_to')
            ->selectRaw('assigned_to as user_id, COUNT(*) as total')
            ->pluck('total', 'user_id');

        $overdue = DB::table('personal_loans as pl')
            ->join('loan_schedules as ls', 'ls.loan_id', '=', 'pl.id')
            ->whereNotNull('pl.assigned_to')
            ->whereIn('pl.status', [2, 3])
            ->where('ls.status', '!=', 1)
            ->whereDate('ls.payment_date', '<', $periodEnd->toDateString())
            ->when($branchId, fn ($query) => $query->where('pl.branch_id', $branchId))
            ->groupBy('pl.assigned_to')
            ->selectRaw('pl.assigned_to as user_id, COUNT(DISTINCT pl.id) as total')
            ->pluck('total', 'user_id');

        $followUps = DB::table('loan_follow_ups')
            ->whereNotNull('assigned_to')
            ->whereBetween('follow_up_at', [$periodStart, $periodEnd])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->groupBy('assigned_to')
            ->selectRaw('assigned_to as user_id, COUNT(*) as total')
            ->pluck('total', 'user_id');

        $collections = DB::table('repayments as r')
            ->join('personal_loans as pl', 'pl.id', '=', 'r.loan_id')
            ->whereNotNull('pl.assigned_to')
            ->where('r.amount', '>', 0)
            ->whereNotIn('r.status', [-1, 2])
            ->whereBetween('r.date_created', [$periodStart, $periodEnd])
            ->where(function ($query) {
                $query->where('r.status', 1)
                    ->orWhereIn('r.payment_status', ['Completed', 'Confirmed']);
            })
            ->when($branchId, fn ($query) => $query->where('pl.branch_id', $branchId))
            ->groupBy('pl.assigned_to')
            ->selectRaw('pl.assigned_to as user_id, SUM(r.amount) as total')
            ->pluck('total', 'user_id');

        $userIds = collect()
            ->merge($users->keys())
            ->merge($assigned->keys())
            ->merge($overdue->keys())
            ->merge($followUps->keys())
            ->merge($collections->keys())
            ->unique()
            ->filter();

        return $userIds->map(function ($userId) use ($users, $assigned, $overdue, $followUps, $collections, $perAssigned, $perPerforming, $perFollowUp, $collectionPercent) {
            $assignedCount = (int) ($assigned[$userId] ?? 0);
            $overdueCount = (int) ($overdue[$userId] ?? 0);
            $performingCount = max($assignedCount - $overdueCount, 0);
            $followUpCount = (int) ($followUps[$userId] ?? 0);
            $collectionAmount = (float) ($collections[$userId] ?? 0);
            $payout = ($assignedCount * $perAssigned)
                + ($performingCount * $perPerforming)
                + ($followUpCount * $perFollowUp)
                + ($collectionAmount * ($collectionPercent / 100));

            return [
                'user_id' => (int) $userId,
                'user_name' => $users[$userId]->name ?? 'User #' . $userId,
                'designation' => $users[$userId]->designation ?? 'Staff',
                'assigned_loans_count' => $assignedCount,
                'performing_loans_count' => $performingCount,
                'overdue_loans_count' => $overdueCount,
                'followups_count' => $followUpCount,
                'collections_amount' => $collectionAmount,
                'payout_amount' => round($payout, 2),
            ];
        })->sortByDesc('payout_amount')->values();
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

            if (!$alreadyPosted && $expenditure->investment_id) {
                Investment::where('id', $expenditure->investment_id)
                    ->decrement('amount', (float) $expenditure->amount);
            }
        }

        $expenditure->status = 'paid';
        $expenditure->paid_at = now();
        $expenditure->paid_by = $userId;
        $expenditure->save();
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

    private function ensureSuperAdmin(Request $request): void
    {
        if (!$request->user()?->isSuperAdmin()) {
            abort(403, 'Access denied. Super Administrator role required.');
        }
    }

    private function mobileMoneyRequestId(): string
    {
        return 'EbP'
            . substr((string) time(), -6)
            . substr(microtime(false), 2, 3)
            . str_pad((string) mt_rand(0, 99), 2, '0', STR_PAD_LEFT);
    }

    private function nextExpenseNumber(): string
    {
        $prefix = 'EXP-' . now()->format('Ymd') . '-';
        $last = Expenditure::where('expense_number', 'like', $prefix . '%')->orderByDesc('expense_number')->value('expense_number');
        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function nextRolloutNumber(): string
    {
        $prefix = 'ROL-' . now()->format('Ymd') . '-';
        $last = ExpenditureRollout::where('rollout_number', 'like', $prefix . '%')->orderByDesc('rollout_number')->value('rollout_number');
        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
