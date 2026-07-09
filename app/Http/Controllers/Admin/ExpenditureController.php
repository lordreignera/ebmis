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
    private const STAFF_MINIMUM_WAGE = 75000.0;
    private const STAFF_WEEKLY_OVERHEAD = 165000.0;
    private const STEWARDSHIP_EXCELLENCE_SCORE = 93.0;
    private const STEWARDSHIP_WATCH_SCORE = 90.0;
    private const STEWARDSHIP_EXCELLENCE_RATE = 20.0;
    private const STEWARDSHIP_WATCH_RATE = 10.0;
    private const COLLECTION_WEIGHT = 30.0;
    private const PAR_WEIGHT = 30.0;
    private const DOCUMENTATION_WEIGHT = 15.0;
    private const GROWTH_WEIGHT = 15.0;
    private const RETENTION_WEIGHT = 10.0;

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

        $expenditure->update([
            'status' => Expenditure::STATUS_APPROVED,
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ]);

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

        $expenditure->update([
            'status' => Expenditure::STATUS_REJECTED,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

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

        if ($expenditure->status === Expenditure::STATUS_PAID) {
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
                $expenditure->status = Expenditure::STATUS_PAYMENT_FAILED;
                $expenditure->save();
            } else {
                $expenditure->status = Expenditure::STATUS_PAYMENT_PENDING;
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
        $this->ensureStaffPaymentRolloutAccess($request);

        $periodStart = $request->get('period_start', now()->startOfWeek()->toDateString());
        $periodEnd = $request->get('period_end', Carbon::parse($periodStart)->copy()->addDays(6)->toDateString());

        $defaults = [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'branch_id' => $request->get('branch_id'),
        ];

        $rows = $this->performanceRows($defaults);
        $policy = $this->staffPaymentPolicy();
        $periodWeeks = $this->weeklyPaymentPeriods(
            Carbon::parse($defaults['period_start'])->startOfDay(),
            Carbon::parse($defaults['period_end'])->endOfDay()
        )->count();
        $rollouts = ExpenditureRollout::with(['branch', 'generatedBy'])
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.expenditures.rollout', array_merge(
            $this->formOptions(),
            compact('defaults', 'rows', 'policy', 'periodWeeks', 'rollouts')
        ));
    }

    public function generateRollout(Request $request)
    {
        $this->ensureStaffPaymentRolloutAccess($request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'expense_account_id' => ['required', 'integer', 'exists:system_accounts,Id'],
            'payment_account_id' => ['nullable', 'integer', 'exists:system_accounts,Id'],
            'investment_id' => ['nullable', 'integer', 'exists:investment,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $rows = $this->performanceRows($validated)->filter(fn ($row) => $row['payout_amount'] > 0)->values();
        if ($rows->isEmpty()) {
            return back()->withInput()->with('error', 'No staff payment amounts were generated for the selected period.');
        }

        $rollout = DB::transaction(function () use ($request, $validated, $rows) {
            $basis = $this->staffPaymentPolicy();
            $basis['weekly_periods_count'] = max(1, (int) $rows->max('weeks_count'));
            $basis['period_calculation'] = $basis['weekly_periods_count'] > 1
                ? 'catch_up_sum_of_weekly_calculations'
                : 'single_weekly_calculation';

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
                'basis' => $basis,
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
                    'principal_collected' => $row['principal_collected'],
                    'interest_collected' => $row['interest_collected'],
                    'late_fees_collected' => $row['late_fees_collected'],
                    'fees_collected' => $row['fees_collected'],
                    'qualified_revenue' => $row['qualified_revenue'],
                    'minimum_wage' => $row['minimum_wage'],
                    'overhead_amount' => $row['overhead_amount'],
                    'net_stewardship_revenue' => $row['net_stewardship_revenue'],
                    'collection_score' => $row['collection_score'],
                    'par_score' => $row['par_score'],
                    'documentation_score' => $row['documentation_score'],
                    'growth_score' => $row['growth_score'],
                    'retention_score' => $row['retention_score'],
                    'stewardship_score' => $row['stewardship_score'],
                    'stewardship_level' => $row['stewardship_level'],
                    'compensation_rate' => $row['compensation_rate'],
                    'stewardship_compensation' => $row['stewardship_compensation'],
                    'payment_blocked' => $row['payment_blocked'],
                    'block_reason' => $row['block_reason'],
                    'payout_amount' => $row['payout_amount'],
                    'notes' => $row['notes'],
                ]);
            }

            return $rollout;
        });

        return redirect()->route('admin.expenditures.rollout.show', $rollout)
            ->with('success', 'Staff payment rollout generated for approval.');
    }

    public function generateIndividualPayout(Request $request)
    {
        $this->ensureStaffPaymentRolloutAccess($request);

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
            return back()->withInput()->with('error', 'This user has no staff payment amount for the selected period.');
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
                ->with('error', 'A staff payment already exists for this user and period.');
        }

        $expense = Expenditure::create([
            'expense_number' => $this->nextExpenseNumber(),
            'type' => 'performance_payout',
            'title' => 'Staff payment - ' . $row['user_name'],
            'description' => $validated['title'] . ' (' . $periodStart . ' to ' . $periodEnd . ')',
            'expense_account_id' => $validated['expense_account_id'],
            'payment_account_id' => $validated['payment_account_id'] ?? null,
            'investment_id' => $validated['investment_id'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'requested_by' => $request->user()?->id,
            'assigned_user_id' => $row['user_id'],
            'amount' => $payoutAmount,
            'expense_date' => now()->toDateString(),
            'status' => Expenditure::STATUS_APPROVED,
            'payment_method' => self::MOBILE_MONEY_METHOD,
            'payment_channel' => 'mobile_money',
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
            'notes' => trim(($validated['notes'] ?? '') . "\nStaff payment basis: "
                . json_encode([
                    'assigned_loans' => $row['assigned_loans_count'],
                    'performing_loans' => $row['performing_loans_count'],
                    'overdue_loans' => $row['overdue_loans_count'],
                    'followups' => $row['followups_count'],
                    'principal_collected' => $row['principal_collected'],
                    'interest_collected' => $row['interest_collected'],
                    'late_fees_collected' => $row['late_fees_collected'],
                    'fees_collected' => $row['fees_collected'],
                    'qualified_revenue' => $row['qualified_revenue'],
                    'stewardship_score' => $row['stewardship_score'],
                    'stewardship_level' => $row['stewardship_level'],
                    'compensation_rate' => $row['compensation_rate'],
                    'minimum_wage' => $row['minimum_wage'],
                    'stewardship_compensation' => $row['stewardship_compensation'],
                    'weekly_periods_count' => $row['weeks_count'] ?? 1,
                    'calculation_notes' => $row['notes'] ?? null,
                ])),
        ]);

        return redirect()
            ->route('admin.expenditures.show', $expense)
            ->with('success', 'Individual staff payment created. Complete the mobile money payment from this page.');
    }

    public function showRollout(ExpenditureRollout $rollout)
    {
        $this->ensureStaffPaymentRolloutAccess(request());

        $rollout->load(['items.user', 'items.expenditure', 'expenseAccount', 'paymentAccount', 'investment', 'branch', 'generatedBy']);
        $policy = $rollout->basis ?: $this->staffPaymentPolicy();

        return view('admin.expenditures.rollout-show', array_merge(
            compact('rollout', 'policy'),
            $this->formOptions()
        ));
    }

    public function approveRollout(Request $request, ExpenditureRollout $rollout)
    {
        $this->ensureStaffPaymentRolloutAccess($request);

        if ($rollout->status !== 'draft') {
            return back()->with('error', 'Only draft rollouts can be approved.');
        }

        $rollout->update([
            'status' => 'approved',
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Staff payment rollout approved.');
    }

    public function payRollout(Request $request, ExpenditureRollout $rollout)
    {
        $this->ensureStaffPaymentRolloutAccess($request);

        if ($rollout->status === 'paid') {
            return back()->with('error', 'This rollout has already been paid.');
        }

        if ($rollout->status === 'payment_pending') {
            return back()->with('error', 'This rollout already has pending mobile-money payments. Open the generated payouts and check their statuses before retrying.');
        }

        if ($rollout->status === 'payment_failed') {
            return back()->with('error', 'This rollout has failed mobile-money payouts. Open each failed payout from the rollout and retry it from the expenditure page.');
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

        if ($payoutItems->isEmpty()) {
            $this->refreshRolloutPaymentStatus($rollout->id, $request->user()?->id);
            return back()->with('info', 'There are no unsent staff payouts left in this rollout. Check the existing payout statuses.');
        }

        $blockedItems = $payoutItems
            ->filter(fn ($item) => (bool) $item->payment_blocked)
            ->map(fn ($item) => ($item->user->name ?? 'User #' . $item->user_id) . ': ' . ($item->block_reason ?: 'Payment blocked by policy'))
            ->values();

        if ($blockedItems->isNotEmpty()) {
            return back()->withInput()->with('error', 'Resolve blocked staff payments before sending mobile money: ' . $blockedItems->implode('; '));
        }

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
                    'title' => 'Staff payment - ' . ($item->user->name ?? 'User #' . $item->user_id),
                    'description' => $rollout->title . ' (' . $rollout->period_start->format('Y-m-d') . ' to ' . $rollout->period_end->format('Y-m-d') . ')',
                    'expense_account_id' => $rollout->expense_account_id,
                    'payment_account_id' => $validated['payment_account_id'],
                    'investment_id' => $validated['investment_id'],
                    'branch_id' => $rollout->branch_id,
                    'requested_by' => $rollout->generated_by,
                    'assigned_user_id' => $item->user_id,
                    'amount' => $item->payout_amount,
                    'expense_date' => now()->toDateString(),
                    'status' => Expenditure::STATUS_APPROVED,
                    'payment_method' => self::MOBILE_MONEY_METHOD,
                    'payment_channel' => 'mobile_money',
                    'approved_by' => $request->user()?->id,
                    'approved_at' => now(),
                    'rollout_batch_id' => $rollout->id,
                    'notes' => 'Auto-created from staff payment rollout ' . $rollout->rollout_number . "\nStaff payment basis: " . json_encode([
                        'principal_collected' => (float) $item->principal_collected,
                        'interest_collected' => (float) $item->interest_collected,
                        'late_fees_collected' => (float) $item->late_fees_collected,
                        'fees_collected' => (float) $item->fees_collected,
                        'qualified_revenue' => (float) $item->qualified_revenue,
                        'stewardship_score' => (float) $item->stewardship_score,
                        'stewardship_level' => $item->stewardship_level,
                        'compensation_rate' => (float) $item->compensation_rate,
                        'minimum_wage' => (float) $item->minimum_wage,
                        'stewardship_compensation' => (float) $item->stewardship_compensation,
                        'calculation_notes' => $item->notes,
                    ]),
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

        return back()->with('info', 'Staff mobile-money rollout initiated. Check each payout status before treating pending items as paid.');
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

            if ($expenditure->status === Expenditure::STATUS_APPROVED && !$expenditure->approved_at) {
                $expenditure->approved_by = $request->user()?->id;
                $expenditure->approved_at = now();
            }

            $immediateSuccess = ($result['status_code'] ?? null) === '00';

            if (($result['success'] ?? false) && $immediateSuccess) {
                $this->markExpenditurePaid($expenditure, $request->user()?->id);
            } elseif ($result['success'] ?? false) {
                $expenditure->status = Expenditure::STATUS_PAYMENT_PENDING;
                $expenditure->save();
            } else {
                $expenditure->status = Expenditure::STATUS_PAYMENT_FAILED;
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

    private function performanceRows(array $input): Collection
    {
        $periodStart = Carbon::parse($input['period_start'] ?? now()->startOfWeek())->startOfDay();
        $periodEnd = Carbon::parse($input['period_end'] ?? now())->endOfDay();
        if ($periodEnd->lt($periodStart)) {
            return collect();
        }

        $weeklyPeriods = $this->weeklyPaymentPeriods($periodStart, $periodEnd);

        if ($weeklyPeriods->count() <= 1) {
            return $this->weeklyPerformanceRows($input);
        }

        $weeklyRows = collect();
        foreach ($weeklyPeriods as $period) {
            $rows = $this->weeklyPerformanceRows(array_merge($input, [
                'period_start' => $period['start']->toDateString(),
                'period_end' => $period['end']->toDateString(),
            ]));

            foreach ($rows as $row) {
                $weeklyRows->push(array_merge($row, [
                    'week_start' => $period['start']->toDateString(),
                    'week_end' => $period['end']->toDateString(),
                ]));
            }
        }

        return $this->aggregateWeeklyStaffPaymentRows($weeklyRows, $weeklyPeriods->count());
    }

    private function weeklyPerformanceRows(array $input): Collection
    {
        $periodStart = Carbon::parse($input['period_start'] ?? now()->startOfWeek())->startOfDay();
        $periodEnd = Carbon::parse($input['period_end'] ?? now())->endOfDay();
        if ($periodEnd->lt($periodStart)) {
            return collect();
        }

        $branchId = !empty($input['branch_id']) ? (int) $input['branch_id'] : null;

        $assigned = PersonalLoan::query()
            ->whereNotNull('assigned_to')
            ->whereIn('status', [2])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->groupBy('assigned_to')
            ->selectRaw('assigned_to as user_id, COUNT(*) as total')
            ->pluck('total', 'user_id');

        $overdue = DB::table('personal_loans as pl')
            ->join('loan_schedules as ls', 'ls.loan_id', '=', 'pl.id')
            ->whereNotNull('pl.assigned_to')
            ->whereIn('pl.status', [2])
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

        $repaymentBreakdown = $this->repaymentBreakdownByUser($periodStart, $periodEnd, $branchId);
        $feesCollected = $this->feeCollectionsByUser($periodStart, $periodEnd, $branchId);
        $expectedDue = $this->expectedCollectionsByUser($periodStart, $periodEnd, $branchId);
        $par30 = $this->par30ByUser($periodEnd, $branchId);
        $documentation = $this->documentationByUser($branchId);
        $growth = $this->growthByUser($periodStart, $periodEnd, $branchId);
        $retention = $this->retentionByUser($branchId);

        $activityUserIds = collect()
            ->merge($assigned->keys())
            ->merge($overdue->keys())
            ->merge($followUps->keys())
            ->merge($repaymentBreakdown->keys())
            ->merge($feesCollected->keys())
            ->merge($expectedDue->keys())
            ->merge($par30->keys())
            ->merge($documentation->keys())
            ->merge($growth->keys())
            ->unique()
            ->filter()
            ->values();

        $users = User::where('status', 'active')
            ->where(function ($query) use ($activityUserIds) {
                $query->where(function ($staffQuery) {
                    $staffQuery->where('designation', 'like', '%officer%')
                        ->orWhere('designation', 'like', '%loan%')
                        ->orWhere('designation', 'like', '%field%')
                        ->orWhere('designation', 'like', '%collection%')
                        ->orWhere('designation', 'like', '%branch manager%');

                    if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
                        $staffQuery->orWhereHas('roles', function ($roleQuery) {
                            $roleQuery->whereIn('name', [
                                'Loan Officer',
                                'Credit Officer',
                                'Field Officer',
                                'Collections Officer',
                                'Branch Manager',
                            ]);
                        });
                    }
                });

                if ($activityUserIds->isNotEmpty()) {
                    $query->orWhereIn('id', $activityUserIds->all());
                }
            })
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id', 'designation'])
            ->keyBy('id');

        $paidUserIds = $this->staffPaymentUserIdsForPeriod($periodStart, $periodEnd);

        return $users->keys()->diff($paidUserIds)->map(function ($userId) use ($users, $assigned, $overdue, $followUps, $repaymentBreakdown, $feesCollected, $expectedDue, $par30, $documentation, $growth, $retention) {
            $userId = (int) $userId;
            $assignedCount = (int) ($assigned[$userId] ?? 0);
            $overdueCount = (int) ($overdue[$userId] ?? 0);
            $performingCount = max($assignedCount - $overdueCount, 0);
            $followUpCount = (int) ($followUps[$userId] ?? 0);
            $breakdown = $repaymentBreakdown[$userId] ?? [
                'principal' => 0,
                'interest' => 0,
                'late_fees' => 0,
                'total' => 0,
            ];
            $principalCollected = (float) ($breakdown['principal'] ?? 0);
            $interestCollected = (float) ($breakdown['interest'] ?? 0);
            $lateFeesCollected = (float) ($breakdown['late_fees'] ?? 0);
            $collectionAmount = (float) ($breakdown['total'] ?? 0);
            $feesAmount = (float) ($feesCollected[$userId] ?? 0);
            $qualifiedRevenue = $interestCollected + $lateFeesCollected + $feesAmount;
            $netRevenue = max(0, $qualifiedRevenue - self::STAFF_WEEKLY_OVERHEAD);

            $expectedAmount = (float) ($expectedDue[$userId] ?? 0);
            $collectionScore = $expectedAmount > 0
                ? min(100, ($collectionAmount / $expectedAmount) * 100)
                : ($collectionAmount > 0 ? 100 : 0);

            $parCount = (int) ($par30[$userId] ?? 0);
            $parScore = $assignedCount > 0
                ? max(0, 100 - (($parCount / $assignedCount) * 100))
                : 0;

            $documented = (int) data_get($documentation, "{$userId}.documented", 0);
            $documentationTotal = (int) data_get($documentation, "{$userId}.total", 0);
            $documentationScore = $documentationTotal > 0
                ? min(100, ($documented / $documentationTotal) * 100)
                : 0;

            $growthScore = (float) ($growth[$userId] ?? 0);
            $retentionScore = (float) ($retention[$userId] ?? 0);
            $stewardshipScore = $this->weightedStewardshipScore(
                $collectionScore,
                $parScore,
                $documentationScore,
                $growthScore,
                $retentionScore
            );
            [$stewardshipLevel, $compensationRate] = $this->stewardshipLevelAndRate($stewardshipScore);
            $stewardshipCompensation = $netRevenue * ($compensationRate / 100);
            $payout = self::STAFF_MINIMUM_WAGE + $stewardshipCompensation;

            return [
                'user_id' => $userId,
                'user_name' => $users[$userId]->name ?? 'User #' . $userId,
                'designation' => $users[$userId]->designation ?? 'Staff',
                'assigned_loans_count' => $assignedCount,
                'performing_loans_count' => $performingCount,
                'overdue_loans_count' => $overdueCount,
                'followups_count' => $followUpCount,
                'collections_amount' => round($collectionAmount, 2),
                'principal_collected' => round($principalCollected, 2),
                'interest_collected' => round($interestCollected, 2),
                'late_fees_collected' => round($lateFeesCollected, 2),
                'fees_collected' => round($feesAmount, 2),
                'qualified_revenue' => round($qualifiedRevenue, 2),
                'minimum_wage' => self::STAFF_MINIMUM_WAGE,
                'overhead_amount' => self::STAFF_WEEKLY_OVERHEAD,
                'net_stewardship_revenue' => round($netRevenue, 2),
                'collection_score' => round($collectionScore, 2),
                'par_score' => round($parScore, 2),
                'documentation_score' => round($documentationScore, 2),
                'growth_score' => round($growthScore, 2),
                'retention_score' => round($retentionScore, 2),
                'stewardship_score' => round($stewardshipScore, 2),
                'stewardship_level' => $stewardshipLevel,
                'compensation_rate' => $compensationRate,
                'stewardship_compensation' => round($stewardshipCompensation, 2),
                'payment_blocked' => false,
                'block_reason' => null,
                'payout_amount' => round($payout, 2),
                'notes' => null,
            ];
        })->sortByDesc('payout_amount')->values();
    }

    private function weeklyPaymentPeriods(Carbon $periodStart, Carbon $periodEnd): Collection
    {
        $periods = collect();
        $cursor = $periodStart->copy()->startOfDay();
        $finalEnd = $periodEnd->copy()->endOfDay();

        while ($cursor->lte($finalEnd)) {
            $sliceStart = $cursor->copy();
            $sliceEnd = $cursor->copy()->addDays(6)->endOfDay();
            if ($sliceEnd->gt($finalEnd)) {
                $sliceEnd = $finalEnd->copy();
            }

            $periods->push([
                'start' => $sliceStart,
                'end' => $sliceEnd,
            ]);

            $cursor = $sliceEnd->copy()->addSecond()->startOfDay();
        }

        return $periods;
    }

    private function staffPaymentUserIdsForPeriod(Carbon $periodStart, Carbon $periodEnd): Collection
    {
        $start = $periodStart->toDateString();
        $end = $periodEnd->toDateString();

        return Expenditure::where('type', 'performance_payout')
            ->whereNotNull('assigned_user_id')
            ->whereNotIn('status', ['rejected', 'cancelled'])
            ->where(function ($query) use ($start, $end) {
                $query->where(function ($descriptionQuery) use ($start, $end) {
                    $descriptionQuery->where('description', 'like', "%{$start}%")
                        ->where('description', 'like', "%{$end}%");
                })->orWhere(function ($notesQuery) use ($start, $end) {
                    $notesQuery->where('notes', 'like', "%{$start}%")
                        ->where('notes', 'like', "%{$end}%");
                });
            })
            ->pluck('assigned_user_id')
            ->unique()
            ->values();
    }

    private function aggregateWeeklyStaffPaymentRows(Collection $weeklyRows, int $weeksCount): Collection
    {
        if ($weeklyRows->isEmpty()) {
            return collect();
        }

        return $weeklyRows
            ->groupBy('user_id')
            ->map(function (Collection $rows) use ($weeksCount) {
                $first = $rows->first();
                $includedWeeks = $rows->count();
                $scoreAverage = fn (string $key) => round((float) $rows->avg($key), 2);
                $moneySum = fn (string $key) => round((float) $rows->sum($key), 2);
                $blockedRows = $rows->filter(fn ($row) => (bool) ($row['payment_blocked'] ?? false));
                $weekLabels = $rows
                    ->map(fn ($row) => ($row['week_start'] ?? '') . ' to ' . ($row['week_end'] ?? ''))
                    ->filter()
                    ->implode('; ');
                $stewardshipScore = $scoreAverage('stewardship_score');
                [$stewardshipLevel, $unusedRate] = $this->stewardshipLevelAndRate($stewardshipScore);

                return [
                    'user_id' => (int) $first['user_id'],
                    'user_name' => $first['user_name'],
                    'designation' => $first['designation'],
                    'assigned_loans_count' => (int) $rows->max('assigned_loans_count'),
                    'performing_loans_count' => (int) $rows->max('performing_loans_count'),
                    'overdue_loans_count' => (int) $rows->max('overdue_loans_count'),
                    'followups_count' => (int) $rows->sum('followups_count'),
                    'collections_amount' => $moneySum('collections_amount'),
                    'principal_collected' => $moneySum('principal_collected'),
                    'interest_collected' => $moneySum('interest_collected'),
                    'late_fees_collected' => $moneySum('late_fees_collected'),
                    'fees_collected' => $moneySum('fees_collected'),
                    'qualified_revenue' => $moneySum('qualified_revenue'),
                    'minimum_wage' => $moneySum('minimum_wage'),
                    'overhead_amount' => $moneySum('overhead_amount'),
                    'net_stewardship_revenue' => $moneySum('net_stewardship_revenue'),
                    'collection_score' => $scoreAverage('collection_score'),
                    'par_score' => $scoreAverage('par_score'),
                    'documentation_score' => $scoreAverage('documentation_score'),
                    'growth_score' => $scoreAverage('growth_score'),
                    'retention_score' => $scoreAverage('retention_score'),
                    'stewardship_score' => $stewardshipScore,
                    'stewardship_level' => $stewardshipLevel,
                    'compensation_rate' => round((float) $rows->avg('compensation_rate'), 2),
                    'stewardship_compensation' => $moneySum('stewardship_compensation'),
                    'payment_blocked' => $blockedRows->isNotEmpty(),
                    'block_reason' => $blockedRows->pluck('block_reason')->filter()->unique()->implode('; ') ?: null,
                    'payout_amount' => $moneySum('payout_amount'),
                    'weeks_count' => $includedWeeks,
                    'notes' => $includedWeeks . ' weekly calculations included from selected ' . $weeksCount . ': ' . $weekLabels,
                ];
            })
            ->sortByDesc('payout_amount')
            ->values();
    }

    private function repaymentBreakdownByUser(Carbon $periodStart, Carbon $periodEnd, ?int $branchId): Collection
    {
        $periodRepayments = $this->confirmedRepaymentQuery()
            ->leftJoin('loan_schedules as ls', 'ls.id', '=', 'r.schedule_id')
            ->whereBetween('r.date_created', [$periodStart, $periodEnd])
            ->when($branchId, fn ($query) => $query->where('pl.branch_id', $branchId))
            ->orderBy('r.schedule_id')
            ->orderBy('r.date_created')
            ->orderBy('r.id')
            ->get([
                'r.id',
                'r.schedule_id',
                'r.amount',
                'pl.assigned_to as user_id',
                'ls.interest',
                'ls.principal',
            ]);

        if ($periodRepayments->isEmpty()) {
            return collect();
        }

        $scheduleIds = $periodRepayments->pluck('schedule_id')->filter()->unique()->values();
        $paidBefore = $scheduleIds->isEmpty()
            ? collect()
            : $this->confirmedRepaymentQuery()
                ->whereIn('r.schedule_id', $scheduleIds)
                ->where('r.date_created', '<', $periodStart)
                ->groupBy('r.schedule_id')
                ->selectRaw('r.schedule_id, SUM(r.amount) as total')
                ->pluck('total', 'schedule_id');

        $runningPaid = $paidBefore
            ->map(fn ($amount) => (float) $amount)
            ->all();

        $totals = collect();
        foreach ($periodRepayments as $repayment) {
            $userId = (int) $repayment->user_id;
            $scheduleId = (int) ($repayment->schedule_id ?? 0);
            $paidToDate = (float) ($runningPaid[$scheduleId] ?? 0);
            $interestDue = (float) ($repayment->interest ?? 0);
            $principalDue = (float) ($repayment->principal ?? 0);
            $amountRemaining = (float) $repayment->amount;

            $interestPaidBefore = min($paidToDate, $interestDue);
            $principalPaidBefore = min(max($paidToDate - $interestDue, 0), $principalDue);
            $interestPart = min($amountRemaining, max($interestDue - $interestPaidBefore, 0));
            $amountRemaining -= $interestPart;
            $principalPart = min($amountRemaining, max($principalDue - $principalPaidBefore, 0));
            $amountRemaining -= $principalPart;
            $lateFeePart = max($amountRemaining, 0);

            $current = $totals->get($userId, [
                'principal' => 0,
                'interest' => 0,
                'late_fees' => 0,
                'total' => 0,
            ]);

            $current['principal'] += $principalPart;
            $current['interest'] += $interestPart;
            $current['late_fees'] += $lateFeePart;
            $current['total'] += (float) $repayment->amount;
            $totals->put($userId, $current);

            if ($scheduleId > 0) {
                $runningPaid[$scheduleId] = $paidToDate + (float) $repayment->amount;
            }
        }

        return $totals;
    }

    private function feeCollectionsByUser(Carbon $periodStart, Carbon $periodEnd, ?int $branchId): Collection
    {
        if (!Schema::hasTable('fees')) {
            return collect();
        }

        return DB::table('fees as f')
            ->leftJoin('personal_loans as pl', 'pl.id', '=', 'f.loan_id')
            ->leftJoin('users as u', 'u.id', '=', 'f.added_by')
            ->where('f.status', 1)
            ->where('f.amount', '>', 0)
            ->whereBetween('f.datecreated', [$periodStart, $periodEnd])
            ->whereRaw('COALESCE(pl.assigned_to, f.added_by) IS NOT NULL')
            ->where(function ($query) {
                $description = 'LOWER(COALESCE(f.description, \'\'))';
                $query->whereRaw("{$description} NOT LIKE ?", ['%insurance%'])
                    ->whereRaw("{$description} NOT LIKE ?", ['%security%'])
                    ->whereRaw("{$description} NOT LIKE ?", ['%savings%'])
                    ->whereRaw("{$description} NOT LIKE ?", ['%deposit%']);
            })
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($branchQuery) use ($branchId) {
                    $branchQuery->where('pl.branch_id', $branchId)
                        ->orWhere('u.branch_id', $branchId);
                });
            })
            ->groupBy(DB::raw('COALESCE(pl.assigned_to, f.added_by)'))
            ->selectRaw('COALESCE(pl.assigned_to, f.added_by) as user_id, SUM(f.amount) as total')
            ->pluck('total', 'user_id');
    }

    private function expectedCollectionsByUser(Carbon $periodStart, Carbon $periodEnd, ?int $branchId): Collection
    {
        return DB::table('loan_schedules as ls')
            ->join('personal_loans as pl', 'pl.id', '=', 'ls.loan_id')
            ->whereNotNull('pl.assigned_to')
            ->whereIn('pl.status', [2])
            ->whereBetween('ls.payment_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->when($branchId, fn ($query) => $query->where('pl.branch_id', $branchId))
            ->groupBy('pl.assigned_to')
            ->selectRaw('pl.assigned_to as user_id, SUM(ls.payment) as total')
            ->pluck('total', 'user_id');
    }

    private function par30ByUser(Carbon $periodEnd, ?int $branchId): Collection
    {
        return DB::table('personal_loans as pl')
            ->join('loan_schedules as ls', 'ls.loan_id', '=', 'pl.id')
            ->whereNotNull('pl.assigned_to')
            ->whereIn('pl.status', [2])
            ->where('ls.status', '!=', 1)
            ->whereDate('ls.payment_date', '<', $periodEnd->copy()->subDays(30)->toDateString())
            ->when($branchId, fn ($query) => $query->where('pl.branch_id', $branchId))
            ->groupBy('pl.assigned_to')
            ->selectRaw('pl.assigned_to as user_id, COUNT(DISTINCT pl.id) as total')
            ->pluck('total', 'user_id');
    }

    private function documentationByUser(?int $branchId): Collection
    {
        $loans = PersonalLoan::query()
            ->whereNotNull('assigned_to')
            ->whereIn('status', [2])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->get([
                'id',
                'assigned_to',
                'immovable_assets',
                'moveable_assets',
                'intellectual_property',
                'stocks_collateral',
                'livestock_collateral',
            ]);

        if ($loans->isEmpty()) {
            return collect();
        }

        $loanIds = $loans->pluck('id')->all();
        $documentedByFile = Schema::hasTable('loan_collateral_documents')
            ? DB::table('loan_collateral_documents')
                ->where('loan_type', 'personal')
                ->whereIn('loan_id', $loanIds)
                ->pluck('loan_id')
                ->unique()
                ->flip()
            : collect();

        $documentedByCash = Schema::hasTable('cash_securities')
            ? DB::table('cash_securities')
                ->whereIn('loan_id', $loanIds)
                ->where('status', 1)
                ->pluck('loan_id')
                ->unique()
                ->flip()
            : collect();

        return $loans
            ->groupBy('assigned_to')
            ->map(function ($items) use ($documentedByFile, $documentedByCash) {
                $documented = $items->filter(function ($loan) use ($documentedByFile, $documentedByCash) {
                    return $documentedByFile->has($loan->id)
                        || $documentedByCash->has($loan->id)
                        || $this->loanHasCollateralText($loan);
                })->count();

                return [
                    'documented' => $documented,
                    'total' => $items->count(),
                ];
            });
    }

    private function growthByUser(Carbon $periodStart, Carbon $periodEnd, ?int $branchId): Collection
    {
        $days = max(1, (int) $periodStart->diffInDays($periodEnd) + 1);
        $previousStart = $periodStart->copy()->subDays($days);
        $previousEnd = $periodStart->copy()->subSecond();

        $current = $this->newLoansByUser($periodStart, $periodEnd, $branchId);
        $previous = $this->newLoansByUser($previousStart, $previousEnd, $branchId);

        return collect()
            ->merge($current->keys())
            ->merge($previous->keys())
            ->unique()
            ->mapWithKeys(function ($userId) use ($current, $previous) {
                $currentCount = (int) ($current[$userId] ?? 0);
                $previousCount = (int) ($previous[$userId] ?? 0);

                if ($previousCount <= 0) {
                    return [$userId => $currentCount > 0 ? 100 : 0];
                }

                $growthPercent = (($currentCount - $previousCount) / $previousCount) * 100;
                return [$userId => $growthPercent > 0 ? min(100, ($growthPercent / 7) * 100) : 0];
            });
    }

    private function retentionByUser(?int $branchId): Collection
    {
        $base = PersonalLoan::query()
            ->whereNotNull('assigned_to')
            ->whereIn('status', [2, 3, 6])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->groupBy('assigned_to')
            ->selectRaw('assigned_to as user_id, COUNT(*) as total')
            ->pluck('total', 'user_id');

        $retained = PersonalLoan::query()
            ->whereNotNull('assigned_to')
            ->whereIn('status', [2, 3])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->groupBy('assigned_to')
            ->selectRaw('assigned_to as user_id, COUNT(*) as total')
            ->pluck('total', 'user_id');

        return $base->mapWithKeys(function ($total, $userId) use ($retained) {
            $score = (int) $total > 0
                ? (((int) ($retained[$userId] ?? 0) / (int) $total) * 100)
                : 0;

            return [$userId => min(100, $score)];
        });
    }

    private function newLoansByUser(Carbon $periodStart, Carbon $periodEnd, ?int $branchId): Collection
    {
        return PersonalLoan::query()
            ->whereNotNull('assigned_to')
            ->whereIn('status', [2, 3])
            ->whereBetween('datecreated', [$periodStart, $periodEnd])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->groupBy('assigned_to')
            ->selectRaw('assigned_to as user_id, COUNT(*) as total')
            ->pluck('total', 'user_id');
    }

    private function confirmedRepaymentQuery()
    {
        return DB::table('repayments as r')
            ->join('personal_loans as pl', 'pl.id', '=', 'r.loan_id')
            ->whereNotNull('pl.assigned_to')
            ->where('r.amount', '>', 0)
            ->whereNotIn('r.status', [-1, 2])
            ->where(function ($query) {
                $query->where('r.status', 1)
                    ->orWhereIn('r.payment_status', ['Completed', 'Confirmed']);
            });
    }

    private function loanHasCollateralText(PersonalLoan $loan): bool
    {
        foreach (['immovable_assets', 'moveable_assets', 'intellectual_property', 'stocks_collateral', 'livestock_collateral'] as $field) {
            $value = trim((string) ($loan->{$field} ?? ''));
            if ($value !== '' && $value !== '[]' && strtolower($value) !== 'null') {
                return true;
            }
        }

        return false;
    }

    private function weightedStewardshipScore(float $collection, float $par, float $documentation, float $growth, float $retention): float
    {
        return (($collection * self::COLLECTION_WEIGHT)
            + ($par * self::PAR_WEIGHT)
            + ($documentation * self::DOCUMENTATION_WEIGHT)
            + ($growth * self::GROWTH_WEIGHT)
            + ($retention * self::RETENTION_WEIGHT)) / 100;
    }

    private function stewardshipLevelAndRate(float $score): array
    {
        if ($score >= self::STEWARDSHIP_EXCELLENCE_SCORE) {
            return ['Stewardship Excellence', self::STEWARDSHIP_EXCELLENCE_RATE];
        }

        if ($score >= self::STEWARDSHIP_WATCH_SCORE) {
            return ['Stewardship Watch', self::STEWARDSHIP_WATCH_RATE];
        }

        return ['Stewardship Intervention', 0.0];
    }

    private function staffPaymentPolicy(): array
    {
        return [
            'policy' => 'portfolio_stewardship_v1',
            'payment_cycle' => 'weekly',
            'catch_up_calculation' => 'sum_each_week_separately',
            'scheduled_payment_time' => 'Saturday 15:00',
            'minimum_wage' => self::STAFF_MINIMUM_WAGE,
            'officer_overhead' => self::STAFF_WEEKLY_OVERHEAD,
            'qualified_revenue_sources' => [
                'interest income',
                'administration fees',
                'registration fees',
                'late payment fees',
                'other approved service charges',
            ],
            'excluded_sources' => [
                'loan principal repayments',
                'insurance contributions',
                'security revolving fund contributions',
                'client savings',
                'cash security deposits',
                'loan disbursements',
                'internal transfers',
            ],
            'score_weights' => [
                'collection' => self::COLLECTION_WEIGHT,
                'par' => self::PAR_WEIGHT,
                'documentation' => self::DOCUMENTATION_WEIGHT,
                'growth' => self::GROWTH_WEIGHT,
                'retention' => self::RETENTION_WEIGHT,
            ],
            'levels' => [
                'stewardship_excellence' => [
                    'minimum_score' => self::STEWARDSHIP_EXCELLENCE_SCORE,
                    'rate_percent' => self::STEWARDSHIP_EXCELLENCE_RATE,
                ],
                'stewardship_watch' => [
                    'minimum_score' => self::STEWARDSHIP_WATCH_SCORE,
                    'rate_percent' => self::STEWARDSHIP_WATCH_RATE,
                ],
                'stewardship_intervention' => [
                    'maximum_score' => self::STEWARDSHIP_WATCH_SCORE,
                    'rate_percent' => 0,
                ],
            ],
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

            if (!$alreadyPosted && $expenditure->investment_id) {
                Investment::where('id', $expenditure->investment_id)
                    ->decrement('amount', (float) $expenditure->amount);
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
