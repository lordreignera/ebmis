<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffRemunerationCalculation;
use App\Models\StaffRemunerationPolicy;
use App\Models\SystemAccount;
use App\Models\User;
use App\Services\StaffRemuneration\WeeklyRemunerationPreviewService;
use App\Services\StaffRemuneration\WeeklyRemunerationWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffRemunerationController extends Controller
{
    public function __construct(
        private readonly WeeklyRemunerationPreviewService $previewService
    ) {}

    public function index(Request $request)
    {
        if ($request->filled('manual_budget')) {
            $request->merge(['manual_budget' => str_replace(',', '', (string) $request->input('manual_budget'))]);
        }

        $request->validate([
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
            'officer_id' => ['nullable', 'integer'],
            'manual_budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        $periodStart = Carbon::parse($request->input('period_start', now()->startOfWeek()->toDateString()))
            ->startOfWeek(Carbon::MONDAY)
            ->startOfDay();
        $periodEnd = $periodStart->copy()->addDays(6)->endOfDay();

        $policy = StaffRemunerationPolicy::currentForReview();

        $officers = User::query()
            ->where(function ($query) {
                $query->whereNull('user_type')->orWhere('user_type', '!=', 'super_admin');
            })
            ->where(function ($query) {
                $query->where('designation', 'like', '%officer%')
                    ->orWhere('designation', 'like', '%loan%')
                    ->orWhere('designation', 'like', '%field%')
                    ->orWhere('designation', 'like', '%collection%')
                    ->orWhere('designation', 'like', '%branch manager%')
                    ->orWhereHas('roles', function ($roleQuery) {
                        $roleQuery->whereIn('name', [
                            'Loan Officer',
                            'Credit Officer',
                            'Field Officer',
                            'Collections Officer',
                            'Branch Manager',
                        ]);
                    });

                $activityUserIds = DB::table('personal_loans')
                    ->whereNotNull('assigned_to')
                    ->pluck('assigned_to')
                    ->merge(DB::table('loan_follow_ups')->whereNotNull('assigned_to')->pluck('assigned_to'))
                    ->unique()
                    ->values();

                if ($activityUserIds->isNotEmpty()) {
                    $query->orWhereIn('id', $activityUserIds->all());
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'status', 'designation', 'branch_id']);

        $savedCalculations = StaffRemunerationCalculation::query()
            ->whereDate('period_start', $periodStart->toDateString())
            ->whereDate('period_end', $periodEnd->toDateString())
            ->get()
            ->keyBy('officer_id');

        $summaryPreviews = $this->previewService->previewMany(
            $officers->pluck('id')->all(),
            $policy,
            $periodStart,
            $periodEnd
        );

        $summaryRows = $officers->map(function (User $officer) use ($summaryPreviews, $savedCalculations) {
            $summary = $summaryPreviews[$officer->id] ?? null;
            $bonusesResolved = $summary
                ? collect($summary['metrics'])->every(fn (array $metric): bool => $metric['is_resolved'])
                : false;

            return [
                'officer' => $officer,
                'preview' => $summary,
                'bonuses_resolved' => $bonusesResolved,
                'calculation' => $savedCalculations->get($officer->id),
            ];
        })->values();

        $selectedOfficer = null;
        $preview = null;
        $savedCalculation = null;

        if ($request->filled('officer_id')) {
            $selectedOfficer = $officers->firstWhere('id', (int) $request->input('officer_id'));
            abort_unless($selectedOfficer, 404, 'The selected officer was not found.');

            $manualBudget = $request->filled('manual_budget')
                ? (float) str_replace(',', '', (string) $request->input('manual_budget'))
                : null;

            $preview = $this->previewService->preview(
                (int) $selectedOfficer->id,
                $policy,
                $periodStart,
                $periodEnd,
                $manualBudget
            );

            $savedCalculation = $savedCalculations->get($selectedOfficer->id);
            if ($savedCalculation && ! $savedCalculation->isReviewable()) {
                $preview = data_get($savedCalculation->input_snapshot, 'preview', $preview);
            }
        }

        $expenseAccounts = SystemAccount::active()
            ->where('category', 'Expense')
            ->orderBy('code')
            ->orderBy('sub_code')
            ->get();

        return view('admin.staff-remuneration.index', compact(
            'policy',
            'officers',
            'selectedOfficer',
            'preview',
            'periodStart',
            'periodEnd',
            'summaryRows',
            'savedCalculation',
            'expenseAccounts'
        ));
    }

    public function storeCalculation(Request $request)
    {
        if ($request->filled('manual_budget')) {
            $request->merge(['manual_budget' => str_replace(',', '', (string) $request->input('manual_budget'))]);
        }

        $validated = $request->validate([
            'officer_id' => ['required', 'integer', 'exists:users,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'manual_budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        $policy = StaffRemunerationPolicy::currentForReview();
        $periodStart = Carbon::parse($validated['period_start'])
            ->startOfWeek(Carbon::MONDAY)
            ->startOfDay();
        $periodEnd = $periodStart->copy()->addDays(6)->endOfDay();
        $manualBudget = array_key_exists('manual_budget', $validated) && $validated['manual_budget'] !== null
            ? (float) $validated['manual_budget']
            : null;

        $preview = $this->previewService->preview(
            (int) $validated['officer_id'],
            $policy,
            $periodStart,
            $periodEnd,
            $manualBudget
        );

        try {
            $calculation = $this->previewService->persistPreview(
                $preview,
                $policy,
                $request->user()?->id
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.expenditures.remuneration', [
                'officer_id' => $calculation->officer_id,
                'period_start' => $calculation->period_start->toDateString(),
                'period_end' => $calculation->period_end->toDateString(),
            ])
            ->with('success', 'Weekly remuneration calculation saved for review. No payment was created.');
    }

    public function createPayout(Request $request, StaffRemunerationCalculation $calculation, WeeklyRemunerationWorkflowService $workflow)
    {
        $data = $request->validate([
            'expense_account_id' => ['required', 'integer', 'exists:system_accounts,Id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $alreadyLinked = (bool) $calculation->expenditure_id;
        $expense = $workflow->requestPayout($calculation, $request->user()->id, (int) $data['expense_account_id'], $data['notes'] ?? null);

        return redirect()->route('admin.expenditures.show', $expense)->with(
            $alreadyLinked ? 'info' : 'success',
            $alreadyLinked ? 'A payout request already exists for this calculation.' : 'Payout request created from the saved calculation. It is pending approval.'
        );
    }

    public function reopenRejectedPayout(Request $request, StaffRemunerationCalculation $calculation, WeeklyRemunerationWorkflowService $workflow)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $record = $workflow->reopenRejectedPayout($calculation, $request->user()->id, $data['reason']);

        return redirect()->route('admin.expenditures.remuneration', [
            'officer_id' => $record->officer_id, 'period_start' => $record->period_start->toDateString(),
        ])->with('success', 'Rejected request preserved. Review and save the corrected calculation before requesting a replacement payout.');
    }

    public function reviewSpendingReport(Request $request, StaffRemunerationCalculation $calculation, WeeklyRemunerationWorkflowService $workflow)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,return'],
            'reason' => ['required', 'string', 'max:2000'],
            'funding_verified' => ['required_if:decision,approve', 'accepted_if:decision,approve'],
            'expenses_verified' => ['required_if:decision,approve', 'accepted_if:decision,approve'],
        ]);
        $workflow->reviewReport($calculation, $request->user()->id, $data['decision'] === 'approve', $data['reason']);

        return back()->with('success', $data['decision'] === 'approve' ? 'Spending report approved and calculation refreshed.' : 'Spending report returned to the officer.');
    }

    public function postFundAllocations(Request $request, StaffRemunerationCalculation $calculation, WeeklyRemunerationWorkflowService $workflow)
    {
        $journal = $workflow->postFundAllocations($calculation, $request->user()->id);

        return back()->with('success', 'Officer-tagged fund allocations recorded in journal '.$journal->journal_number.'.');
    }

    public function settleZeroPayout(Request $request, StaffRemunerationCalculation $calculation, WeeklyRemunerationWorkflowService $workflow)
    {
        $workflow->settleZeroPayout($calculation, $request->user()->id);

        return back()->with('success', 'Zero-net week settled; its deficit balance is retained for subsequent periods.');
    }
}
