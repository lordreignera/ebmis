<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffRemunerationCalculation;
use App\Models\StaffRemunerationPolicy;
use App\Services\StaffRemuneration\WeeklyRemunerationPreviewService;
use App\Services\StaffRemuneration\WeeklyRemunerationWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StaffRemunerationPerformanceController extends Controller
{
    public function __construct(
        private readonly WeeklyRemunerationPreviewService $previewService
    ) {}

    public function index(Request $request)
    {
        $request->validate([
            'view' => ['nullable', 'in:week,month'],
            'week_start' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $user = $request->user();
        $mode = $request->input('view', 'week') === 'month' ? 'month' : 'week';
        $policy = StaffRemunerationPolicy::currentForReview();

        if ($mode === 'month') {
            $month = $this->month($request->input('month'));
            $monthly = $this->previewService->monthlyPreview($user->id, $policy, $month);
            $weeks = $monthly['weeks'];
            $periodStart = $month->copy()->startOfMonth();
            $periodEnd = $month->copy()->endOfMonth();
        } else {
            $periodStart = $this->weekStart($request->input('week_start'));
            $periodEnd = $periodStart->copy()->addDays(6)->endOfDay();
            $weeks = [$this->previewService->displayPreview($user->id, $policy, $periodStart, $periodEnd)];
            $monthly = null;
        }

        $calculation = $mode === 'week' ? StaffRemunerationCalculation::forOfficerPeriod($user->id, $periodStart, $periodEnd)->first() : null;
        $report = data_get($calculation?->budget_snapshot, 'report');

        return view('admin.staff-remuneration.performance', compact(
            'mode',
            'policy',
            'weeks',
            'monthly',
            'periodStart',
            'periodEnd',
            'calculation',
            'report'
        ));
    }

    public function submitReport(Request $request, WeeklyRemunerationWorkflowService $workflow)
    {
        $request->validate(['items' => ['sometimes', 'array', 'max:100'], 'items.*' => ['array']]);
        $items = collect($request->input('items', []))->filter(fn ($row) => ! empty($row['amount']) || ! empty($row['description']))
            ->map(fn ($row) => [...$row, 'amount' => str_replace(',', '', (string) ($row['amount'] ?? ''))])->values()->all();
        $request->merge(['items' => $items, 'received_amount' => str_replace(',', '', (string) $request->input('received_amount'))]);
        $data = $request->validate([
            'week_start' => ['required', 'date'],
            'received_amount' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'funding_reference' => ['nullable', 'string', 'max:500'],
            'items' => ['present', 'array', 'max:100'],
            'items.*.date' => ['required', 'date'],
            'items.*.category' => ['required', 'in:transport,communication,field_visit,stationery,other'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999'],
            'items.*.reference' => ['required', 'string', 'max:500'],
            'items.*.expenditure_id' => ['nullable', 'integer', 'exists:expenditures,id'],
            'activities_completed' => ['nullable', 'boolean'],
            'report_complete' => ['required', 'accepted'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $calculation = $workflow->submitReport($request->user()->id, StaffRemunerationPolicy::currentForReview(), $data['week_start'], $data);

        return redirect()->route('admin.remuneration.performance', ['week_start' => $calculation->period_start->toDateString()])
            ->with('success', 'Weekly spending report submitted for administrative review.');
    }

    private function weekStart(?string $value): Carbon
    {
        return $value
            ? Carbon::createFromFormat('Y-m-d', $value)->startOfWeek(Carbon::MONDAY)->startOfDay()
            : now()->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    private function month(?string $value): Carbon
    {
        return $value
            ? Carbon::createFromFormat('!Y-m', $value)->startOfMonth()
            : now()->startOfMonth();
    }
}
