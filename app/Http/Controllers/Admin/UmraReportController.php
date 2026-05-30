<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashSecurity;
use App\Models\LoanFollowUp;
use App\Models\PersonalLoan;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UmraReportController extends Controller
{
    private const RISK_CLASSES = [
        'Performing' => ['key' => 'performing', 'rate' => 0.01, 'rank' => 0],
        'Watch' => ['key' => 'watch', 'rate' => 0.05, 'rank' => 1],
        'Substandard' => ['key' => 'substandard', 'rate' => 0.25, 'rank' => 2],
        'Doubtful' => ['key' => 'doubtful', 'rate' => 0.50, 'rank' => 3],
        'Loss' => ['key' => 'loss', 'rate' => 1.00, 'rank' => 4],
    ];

    private $activeLoansCache = null;
    private $activeLoanIdsCache = null;

    /**
     * UMRA Dashboard
     */
    public function dashboard()
    {
        $reportDate = Carbon::now();

        $indicators = $this->getPortfolioIndicators($reportDate);

        $regulatoryStatus = $this->getRegulatoryReturnStatus();
        $riskClassifications = $this->getRiskClassifications();
        $schedule3Summary = $this->getSchedule3Summary($riskClassifications);
        $chartData = $this->getDashboardChartData($schedule3Summary);
        $branchSummary = $this->getBranchSummary($reportDate);

        return view('admin.umra.dashboard', [
            'reportDate' => $reportDate,
            'indicators' => $indicators,
            'regulatoryStatus' => $regulatoryStatus,
            'chartData' => $chartData,
            'branchSummary' => $branchSummary,
        ]);
    }

    /**
     * Portfolio Indicators
     */
    private function getPortfolioIndicators(Carbon $reportDate)
    {
        $activeLoans = $this->getActiveLoans();

        $totalActiveLoanAccounts = $activeLoans->count();
        $grossOutstandingPrincipal = 0;
        $interestOutstanding = 0;
        $requiredProvision = 0;
        $lossClassifiedExposure = 0;
        $par30Loans = 0;
        $par90Loans = 0;
        $writeoffReviewAccounts = 0;

        foreach ($activeLoans as $loan) {
            $outstanding = $this->getLoanOutstandingComponents($loan);
            $loanPrincipal = $outstanding['principal'];
            $loanInterest = $outstanding['interest'];
            $loanOutstanding = $outstanding['total'];
            $risk = $this->getLoanRiskProfile($loan, $reportDate);

            $grossOutstandingPrincipal += $loanPrincipal;
            $interestOutstanding += $loanInterest;
            $requiredProvision += $loanOutstanding * $risk['provision_rate'];

            if ($risk['classification'] === 'Loss') {
                $lossClassifiedExposure += $loanOutstanding;
            }

            if ($risk['dpd'] > 30) {
                $par30Loans++;
            }

            if ($risk['dpd'] > 90) {
                $par90Loans++;
            }

            if ($risk['dpd'] > 270) {
                $writeoffReviewAccounts++;
            }
        }

        $totalOutstanding = $grossOutstandingPrincipal + $interestOutstanding;

        $provisionCoverage = $totalOutstanding > 0
            ? ($requiredProvision / $totalOutstanding) * 100
            : 0;

        $par30 = $totalActiveLoanAccounts > 0
            ? ($par30Loans / $totalActiveLoanAccounts) * 100
            : 0;

        $par90 = $totalActiveLoanAccounts > 0
            ? ($par90Loans / $totalActiveLoanAccounts) * 100
            : 0;

        return [
            'reporting_date' => $reportDate->format('d-M-Y'),
            'total_active_loan_accounts' => $totalActiveLoanAccounts,
            'gross_outstanding_principal' => number_format($grossOutstandingPrincipal, 2),
            'interest_outstanding' => number_format($interestOutstanding, 2),
            'required_provision' => number_format($requiredProvision, 2),
            'provision_coverage' => number_format($provisionCoverage, 1),
            'par_30' => number_format($par30, 1),
            'par_90' => number_format($par90, 1),
            'loss_classified_exposure' => number_format($lossClassifiedExposure, 2),
            'writeoff_review_accounts' => $writeoffReviewAccounts,
        ];
    }

    /**
     * Get Active Loan IDs
     */
    private function getActiveLoanIds()
    {
        if ($this->activeLoanIdsCache !== null) {
            return $this->activeLoanIdsCache;
        }

        $this->activeLoanIdsCache = $this->getActiveLoans()
            ->pluck('id')
            ->values()
            ->all();

        return $this->activeLoanIdsCache;
    }

    /**
     * Active personal loans with schedules loaded once per request.
     */
    private function getActiveLoans()
    {
        if ($this->activeLoansCache !== null) {
            return $this->activeLoansCache;
        }

        $this->activeLoansCache = PersonalLoan::whereIn('status', [2, 3])
            ->whereHas('schedules', function ($query) {
                $query->where('status', '!=', 1);
            })
            ->with(['member', 'schedules', 'branch', 'assignedTo'])
            ->get()
            ->filter(function ($loan) {
                return $loan->getActualStatus() === 'running';
            })
            ->values();

        $this->attachConfirmedPaidAmounts($this->activeLoansCache);

        return $this->activeLoansCache;
    }

    /**
     * Reuse the request-cached active loans when a calculation is scoped by IDs.
     */
    private function getActiveLoansForIds(array $activeLoanIds)
    {
        if (empty($activeLoanIds)) {
            return collect();
        }

        $lookup = array_flip($activeLoanIds);

        return $this->getActiveLoans()
            ->filter(function ($loan) use ($lookup) {
                return isset($lookup[$loan->id]);
            })
            ->values();
    }

    /**
     * Calculate DPD for a Loan
     */
    private function getLoanDPD($loan, ?Carbon $reportDate = null)
    {
        $maxDays = 0;
        $asOfDate = ($reportDate ?: Carbon::now())->copy()->startOfDay();

        foreach ($loan->schedules as $schedule) {

            if ($schedule->status == 1) {
                continue;
            }

            if (!$schedule->payment_date) {
                continue;
            }

            $dueDate = Carbon::parse($schedule->payment_date)->startOfDay();

            if ($dueDate->lt($asOfDate)) {

                $days = (int) $dueDate->diffInDays($asOfDate);

                if ($days > $maxDays) {
                    $maxDays = $days;
                }
            }
        }

        return $maxDays;
    }

    /**
     * Count overdue unpaid instalments for UMRA classification.
     */
    private function getOverdueInstallmentCount($loan, ?Carbon $reportDate = null)
    {
        $asOfDate = ($reportDate ?: Carbon::now())->copy()->startOfDay();

        return $loan->schedules
            ->filter(function ($schedule) use ($asOfDate) {
                if ($schedule->status == 1 || !$schedule->payment_date) {
                    return false;
                }

                return Carbon::parse($schedule->payment_date)->startOfDay()->lt($asOfDate);
            })
            ->count();
    }

    /**
     * UMRA loan risk profile using DPD and overdue instalment count.
     */
    private function getLoanRiskProfile($loan, ?Carbon $reportDate = null)
    {
        $dpd = $this->getLoanDPD($loan, $reportDate);
        $overdueInstallments = $this->getOverdueInstallmentCount($loan, $reportDate);
        $risk = $this->getRiskClassification($dpd, $overdueInstallments);

        return array_merge($risk, [
            'dpd' => $dpd,
            'overdue_installments' => $overdueInstallments,
        ]);
    }

    /**
     * UMRA Risk Classification
     */
    private function getRiskClassification($daysOverdue, int $overdueInstallments = 0)
    {
        $daysClass = $this->getRiskClassByDays($daysOverdue);
        $installmentsClass = $this->getRiskClassByInstallments($overdueInstallments);
        $classification = $this->getWorseRiskClass($daysClass, $installmentsClass);

        return [
            'classification' => $classification,
            'provision_rate' => self::RISK_CLASSES[$classification]['rate'],
            'basis' => $this->getRiskBasis($classification, $daysOverdue, $overdueInstallments),
        ];
    }

    /**
     * Classify by days past due.
     */
    private function getRiskClassByDays(int $daysOverdue)
    {
        if ($daysOverdue <= 0) {
            return 'Performing';
        }

        if ($daysOverdue <= 30) {
            return 'Watch';
        }

        if ($daysOverdue <= 90) {
            return 'Substandard';
        }

        if ($daysOverdue <= 180) {
            return 'Doubtful';
        }

        return 'Loss';
    }

    /**
     * Classify by overdue instalment count, choosing the stricter category when ranges overlap.
     */
    private function getRiskClassByInstallments(int $overdueInstallments)
    {
        if ($overdueInstallments <= 0) {
            return 'Performing';
        }

        if ($overdueInstallments > 6) {
            return 'Loss';
        }

        if ($overdueInstallments >= 4) {
            return 'Doubtful';
        }

        if ($overdueInstallments >= 2) {
            return 'Substandard';
        }

        return 'Watch';
    }

    /**
     * Choose the more severe UMRA class.
     */
    private function getWorseRiskClass(string $firstClass, string $secondClass)
    {
        return self::RISK_CLASSES[$firstClass]['rank'] >= self::RISK_CLASSES[$secondClass]['rank']
            ? $firstClass
            : $secondClass;
    }

    /**
     * Human-readable classification basis for exports and review.
     */
    private function getRiskBasis(string $classification, int $daysOverdue, int $overdueInstallments)
    {
        if ($classification === 'Performing') {
            return 'Performing according to contractual terms';
        }

        return trim($daysOverdue . ' DPD; ' . $overdueInstallments . ' overdue installment(s)');
    }

    /**
     * Risk Badge Colors
     */
    private function getRiskColor($classification)
    {
        return match ($classification) {
            'Performing' => 'success',
            'Watch' => 'warning',
            'Substandard' => 'info',
            'Doubtful' => 'danger',
            'Loss' => 'dark',
            default => 'secondary',
        };
    }

    /**
     * Calculate Required Provision
     */
    private function calculateRequiredProvision(array $activeLoanIds)
    {
        if (empty($activeLoanIds)) {
            return 0;
        }

        $totalProvision = 0;

        $loans = $this->getActiveLoansForIds($activeLoanIds);

        foreach ($loans as $loan) {

            $risk = $this->getLoanRiskProfile($loan);

            $provisionRate = $risk['provision_rate'];

            $outstandingBalance = $this->getLoanOutstandingBalance($loan);

            $totalProvision += ($outstandingBalance * $provisionRate);
        }

        return $totalProvision;
    }

    /**
     * PAR 30
     */
    private function calculatePAR30(array $activeLoanIds)
    {
        $loans = $this->getActiveLoansForIds($activeLoanIds);

        if ($loans->count() == 0) {
            return 0;
        }

        $par30Loans = 0;

        foreach ($loans as $loan) {

            $dpd = $this->getLoanRiskProfile($loan)['dpd'];

            if ($dpd > 30) {
                $par30Loans++;
            }
        }

        return ($par30Loans / $loans->count()) * 100;
    }

    /**
     * PAR 90
     */
    private function calculatePAR90(array $activeLoanIds)
    {
        $loans = $this->getActiveLoansForIds($activeLoanIds);

        if ($loans->count() == 0) {
            return 0;
        }

        $par90Loans = 0;

        foreach ($loans as $loan) {

            $dpd = $this->getLoanRiskProfile($loan)['dpd'];

            if ($dpd > 90) {
                $par90Loans++;
            }
        }

        return ($par90Loans / $loans->count()) * 100;
    }

    /**
     * Loss Classified Exposure
     */
    private function calculateLossClassifiedExposure(array $activeLoanIds)
    {
        if (empty($activeLoanIds)) {
            return 0;
        }

        $lossExposure = 0;

        $loans = $this->getActiveLoansForIds($activeLoanIds);

        foreach ($loans as $loan) {

            $risk = $this->getLoanRiskProfile($loan);

            if ($risk['classification'] === 'Loss') {

                $outstanding = $this->getLoanOutstandingBalance($loan);

                $lossExposure += $outstanding;
            }
        }

        return $lossExposure;
    }

    /**
     * Count loans in a specific UMRA classification.
     */
    private function countLoansByClassification(array $activeLoanIds, string $classification)
    {
        if (empty($activeLoanIds)) {
            return 0;
        }

        return $this->getActiveLoansForIds($activeLoanIds)
            ->filter(function ($loan) use ($classification) {
                $risk = $this->getLoanRiskProfile($loan);

                return $risk['classification'] === $classification;
            })
            ->count();
    }

    /**
     * Loans that should be reviewed for write-off under the 270+ day trigger.
     */
    private function countWriteoffReviewAccounts(array $activeLoanIds)
    {
        if (empty($activeLoanIds)) {
            return 0;
        }

        return $this->getActiveLoansForIds($activeLoanIds)
            ->filter(function ($loan) {
                return $this->getLoanRiskProfile($loan)['dpd'] > 270;
            })
            ->count();
    }

    /**
     * Loan Preview
     */
    public function loanPreview()
    {
        $loanPreview = collect($this->getUmraLoanPreview());

        return view('admin.umra.loan-preview', [
            'loanPreview' => $loanPreview,
            'generatedDate' => Carbon::now(),
        ]);
    }

    /**
     * UMRA loan records register.
     */
    public function loanRecords(Request $request)
    {
        $allLoanRecords = collect($this->getUmraLoanPreview());
        $loanRecords = $this->filterLoanRecords($allLoanRecords, $request);

        return view('admin.umra.loan-records', [
            'loanRecords' => $loanRecords,
            'totalLoanRecords' => $allLoanRecords->count(),
            'filterOptions' => $this->getLoanRecordFilterOptions($allLoanRecords),
            'filters' => $this->normalizeLoanRecordFilters($request),
            'generatedDate' => Carbon::now(),
        ]);
    }

    /**
     * UMRA collateral register.
     */
    public function collateralRegister()
    {
        return view('admin.umra.collateral-register', [
            'collateralRegister' => collect($this->getCollateralRegister()),
            'generatedDate' => Carbon::now(),
        ]);
    }

    /**
     * Search and filter the UMRA loan records register.
     */
    private function filterLoanRecords($loanRecords, Request $request)
    {
        $filters = $this->normalizeLoanRecordFilters($request);

        return $loanRecords
            ->filter(function ($loan) use ($filters) {
                if ($filters['q'] !== '') {
                    $haystack = strtolower(implode(' ', [
                        $loan['client_id'],
                        $loan['client_name'],
                        $loan['loan_account_no'],
                        $loan['financing_account_number'],
                        $loan['branch'],
                        $loan['field_officer'],
                        $loan['loan_product'],
                        $loan['classification'],
                        $loan['loan_status'],
                        $loan['collateral_type'],
                        $loan['latest_follow_up_outcome'] ?? '',
                        $loan['latest_follow_up_method'] ?? '',
                        $loan['latest_follow_up_by'] ?? '',
                        $loan['latest_follow_up_notes'] ?? '',
                    ]));

                    if (!str_contains($haystack, strtolower($filters['q']))) {
                        return false;
                    }
                }

                if ($filters['branch'] !== '' && $loan['branch'] !== $filters['branch']) {
                    return false;
                }

                if ($filters['officer'] !== '' && $loan['field_officer'] !== $filters['officer']) {
                    return false;
                }

                if ($filters['product'] !== '' && $loan['loan_product'] !== $filters['product']) {
                    return false;
                }

                if ($filters['classification'] !== '' && $loan['classification'] !== $filters['classification']) {
                    return false;
                }

                if ($filters['loan_status'] !== '' && $loan['loan_status'] !== $filters['loan_status']) {
                    return false;
                }

                if ($filters['follow_up'] === 'yes' && !($loan['has_follow_up'] ?? false)) {
                    return false;
                }

                if ($filters['follow_up'] === 'no' && ($loan['has_follow_up'] ?? false)) {
                    return false;
                }

                if ($filters['date_from'] !== '' && $loan['disbursement_date_iso'] === '') {
                    return false;
                }

                if ($filters['date_from'] !== '' && $loan['disbursement_date_iso'] !== '') {
                    if ($loan['disbursement_date_iso'] < $filters['date_from']) {
                        return false;
                    }
                }

                if ($filters['date_to'] !== '' && $loan['disbursement_date_iso'] === '') {
                    return false;
                }

                if ($filters['date_to'] !== '' && $loan['disbursement_date_iso'] !== '') {
                    if ($loan['disbursement_date_iso'] > $filters['date_to']) {
                        return false;
                    }
                }

                return true;
            })
            ->values();
    }

    /**
     * Filter dropdown values for the UMRA loan records register.
     */
    private function getLoanRecordFilterOptions($loanRecords)
    {
        return [
            'branches' => $loanRecords->pluck('branch')->filter()->unique()->sort()->values(),
            'officers' => $loanRecords->pluck('field_officer')->filter()->unique()->sort()->values(),
            'products' => $loanRecords->pluck('loan_product')->filter()->unique()->sort()->values(),
            'classifications' => array_keys(self::RISK_CLASSES),
            'statuses' => $loanRecords->pluck('loan_status')->filter()->unique()->sort()->values(),
        ];
    }

    /**
     * Keep filter keys predictable for views and exports.
     */
    private function normalizeLoanRecordFilters(Request $request)
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'branch' => trim((string) $request->query('branch', '')),
            'officer' => trim((string) $request->query('officer', '')),
            'product' => trim((string) $request->query('product', '')),
            'classification' => trim((string) $request->query('classification', '')),
            'loan_status' => trim((string) $request->query('loan_status', '')),
            'follow_up' => trim((string) $request->query('follow_up', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];
    }

    /**
     * Schedule 3 Report
     */
    public function schedule3Report()
    {
        $loanPreview = collect($this->getUmraLoanPreview());
        $riskClassifications = $this->getRiskClassifications();
        $schedule3Summary = $this->getSchedule3Summary($riskClassifications);

        return view('admin.umra.schedule3', [
            'loanPreview' => $loanPreview,
            'riskClassifications' => $riskClassifications,
            'schedule3Summary' => $schedule3Summary,
            'generatedDate' => Carbon::now(),
        ]);
    }

    /**
     * Group active loans into UMRA Schedule 3 risk buckets.
     */
    private function getRiskClassifications()
    {
        $groups = [
            'performing' => collect(),
            'watch' => collect(),
            'substandard' => collect(),
            'doubtful' => collect(),
            'loss' => collect(),
        ];

        $loans = $this->getActiveLoans();

        foreach ($loans as $loan) {
            $risk = $this->getLoanRiskProfile($loan);
            $dpd = $risk['dpd'];
            $key = strtolower($risk['classification']);

            $loan->umra_dpd = $dpd;
            $loan->umra_overdue_installments = $risk['overdue_installments'];
            $loan->umra_classification_basis = $risk['basis'];
            $loan->umra_classification = $risk['classification'];
            $loan->umra_provision_rate = $risk['provision_rate'];
            $loan->umra_outstanding_balance = $this->getLoanOutstandingBalance($loan);
            $loan->umra_required_provision = $loan->umra_outstanding_balance * $risk['provision_rate'];

            if (isset($groups[$key])) {
                $groups[$key]->push($loan);
            }
        }

        return $groups;
    }

    /**
     * Build official Schedule 3 ageing summary rows.
     */
    private function getSchedule3Summary(array $riskClassifications)
    {
        $summary = [
            'standard' => $this->blankSchedule3Rows(),
            'rescheduled' => $this->blankSchedule3Rows(),
        ];

        foreach ($riskClassifications as $loans) {
            foreach ($loans as $loan) {
                $section = (int) ($loan->restructured ?? 0) === 1 ? 'rescheduled' : 'standard';
                $key = self::RISK_CLASSES[$loan->umra_classification]['key'];

                $summary[$section][$key]['accounts']++;
                $summary[$section][$key]['outstanding'] += $loan->umra_outstanding_balance;
                $summary[$section][$key]['required_provision'] += $loan->umra_required_provision;
            }
        }

        $summary['standard_total'] = $this->totalSchedule3Rows($summary['standard']);
        $summary['rescheduled_total'] = $this->totalSchedule3Rows($summary['rescheduled']);
        $summary['grand_total'] = [
            'accounts' => $summary['standard_total']['accounts'] + $summary['rescheduled_total']['accounts'],
            'outstanding' => $summary['standard_total']['outstanding'] + $summary['rescheduled_total']['outstanding'],
            'required_provision' => $summary['standard_total']['required_provision'] + $summary['rescheduled_total']['required_provision'],
        ];

        return $summary;
    }

    /**
     * Chart data for the UMRA dashboard.
     */
    private function getDashboardChartData(array $schedule3Summary)
    {
        $labels = array_keys(self::RISK_CLASSES);
        $counts = [];
        $outstanding = [];
        $provisions = [];

        foreach (self::RISK_CLASSES as $meta) {
            $key = $meta['key'];
            $standard = $schedule3Summary['standard'][$key];
            $rescheduled = $schedule3Summary['rescheduled'][$key];

            $counts[] = $standard['accounts'] + $rescheduled['accounts'];
            $outstanding[] = round($standard['outstanding'] + $rescheduled['outstanding'], 2);
            $provisions[] = round($standard['required_provision'] + $rescheduled['required_provision'], 2);
        }

        return [
            'risk_labels' => $labels,
            'risk_counts' => $counts,
            'risk_outstanding' => $outstanding,
            'risk_provisions' => $provisions,
            'portfolio_total' => round($schedule3Summary['grand_total']['outstanding'], 2),
            'provision_total' => round($schedule3Summary['grand_total']['required_provision'], 2),
        ];
    }

    /**
     * Branch-level UMRA portfolio summary.
     */
    private function getBranchSummary(Carbon $reportDate)
    {
        $branches = [];

        foreach ($this->getActiveLoans() as $loan) {
            $branchName = $loan->branch->name ?? 'Unknown';
            $outstanding = $this->getLoanOutstandingComponents($loan);
            $outstandingPrincipal = $outstanding['principal'];
            $totalOutstanding = $outstanding['total'];
            $risk = $this->getLoanRiskProfile($loan, $reportDate);

            if (!isset($branches[$branchName])) {
                $branches[$branchName] = [
                    'branch' => $branchName,
                    'active_accounts' => 0,
                    'outstanding_principal' => 0,
                    'par30_exposure' => 0,
                    'par30_percent' => 0,
                    'provision_required' => 0,
                    'loss_exposure' => 0,
                ];
            }

            $branches[$branchName]['active_accounts']++;
            $branches[$branchName]['outstanding_principal'] += $outstandingPrincipal;
            $branches[$branchName]['provision_required'] += $totalOutstanding * $risk['provision_rate'];

            if ($risk['dpd'] > 30) {
                $branches[$branchName]['par30_exposure'] += $outstandingPrincipal;
            }

            if ($risk['classification'] === 'Loss') {
                $branches[$branchName]['loss_exposure'] += $totalOutstanding;
            }
        }

        foreach ($branches as &$branch) {
            $branch['par30_percent'] = $branch['outstanding_principal'] > 0
                ? ($branch['par30_exposure'] / $branch['outstanding_principal']) * 100
                : 0;
        }
        unset($branch);

        return collect($branches)
            ->sortBy('branch')
            ->values()
            ->all();
    }

    /**
     * Empty Schedule 3 rows keyed by risk class.
     */
    private function blankSchedule3Rows()
    {
        $rows = [];

        foreach (self::RISK_CLASSES as $label => $meta) {
            $rows[$meta['key']] = [
                'classification' => $label,
                'provision_rate' => $meta['rate'],
                'accounts' => 0,
                'outstanding' => 0,
                'required_provision' => 0,
            ];
        }

        return $rows;
    }

    /**
     * Total Schedule 3 summary rows.
     */
    private function totalSchedule3Rows(array $rows)
    {
        return [
            'accounts' => array_sum(array_column($rows, 'accounts')),
            'outstanding' => array_sum(array_column($rows, 'outstanding')),
            'required_provision' => array_sum(array_column($rows, 'required_provision')),
        ];
    }

    /**
     * Attach confirmed repayments once so UMRA balances do not query per schedule.
     */
    private function attachConfirmedPaidAmounts($loans): void
    {
        $scheduleIds = $loans
            ->flatMap(function ($loan) {
                return $loan->schedules->pluck('id');
            })
            ->values()
            ->all();

        if (empty($scheduleIds)) {
            return;
        }

        $paidBySchedule = DB::table('repayments')
            ->whereIn('schedule_id', $scheduleIds)
            ->where('amount', '>', 0)
            ->whereNotIn('status', [-1, 2])
            ->where(function ($query) {
                $query->where('status', 1)
                    ->orWhere('payment_status', 'Completed');
            })
            ->groupBy('schedule_id')
            ->select('schedule_id', DB::raw('SUM(amount) as total_paid'))
            ->pluck('total_paid', 'schedule_id');

        foreach ($loans as $loan) {
            foreach ($loan->schedules as $schedule) {
                $schedule->umra_paid_amount = max(
                    (float) ($paidBySchedule[$schedule->id] ?? 0),
                    (float) ($schedule->paid ?? 0)
                );
            }
        }
    }

    /**
     * Remaining principal and interest for one unpaid schedule.
     */
    private function getScheduleOutstandingComponents($schedule): array
    {
        $principal = (float) ($schedule->principal ?? 0);
        $interest = (float) ($schedule->interest ?? 0);
        $paid = (float) ($schedule->umra_paid_amount ?? $schedule->paid ?? 0);

        $interestPaid = min($interest, $paid);
        $principalPaid = min($principal, max(0, $paid - $interestPaid));
        $outstandingInterest = max(0, $interest - $interestPaid);
        $outstandingPrincipal = max(0, $principal - $principalPaid);

        return [
            'principal' => $outstandingPrincipal,
            'interest' => $outstandingInterest,
            'total' => $outstandingPrincipal + $outstandingInterest,
        ];
    }

    /**
     * Remaining principal and interest across unpaid schedules.
     */
    private function getLoanOutstandingComponents($loan): array
    {
        $totals = [
            'principal' => 0.0,
            'interest' => 0.0,
            'total' => 0.0,
        ];

        foreach ($loan->schedules->where('status', '!=', 1) as $schedule) {
            $components = $this->getScheduleOutstandingComponents($schedule);
            $totals['principal'] += $components['principal'];
            $totals['interest'] += $components['interest'];
            $totals['total'] += $components['total'];
        }

        return $totals;
    }

    /**
     * Outstanding balance for provision calculations.
     */
    private function getLoanOutstandingBalance($loan)
    {
        return $this->getLoanOutstandingComponents($loan)['total'];
    }

    /**
     * UMRA Loan Preview
     */
    public function getUmraLoanPreview()
    {
        $loans = $this->getActiveLoans();
        $loans->loadMissing(['product', 'disbursements']);
        $activeLoanIds = $this->getActiveLoanIds();

        $cashSecuritiesByLoan = CashSecurity::whereIn('loan_id', $activeLoanIds)
            ->get()
            ->groupBy('loan_id');

        $followUpsByLoan = $this->getLoanFollowUpSummaries($activeLoanIds);

        $reportDate = Carbon::now();
        $preview = [];

        foreach ($loans as $loan) {

            $risk = $this->getLoanRiskProfile($loan);
            $dpd = $risk['dpd'];

            $classification = $risk['classification'];

            $provisionRate = $risk['provision_rate'];

            $badgeColor = $this->getRiskColor($classification);

            $unpaidSchedules = $loan->schedules
                ->where('status', '!=', 1);

            $overdueSchedules = $unpaidSchedules
                ->filter(function ($schedule) use ($reportDate) {
                    if (!$schedule->payment_date) {
                        return false;
                    }

                    return Carbon::parse($schedule->payment_date)->lt($reportDate->copy()->startOfDay());
                });

            $outstanding = $this->getLoanOutstandingComponents($loan);
            $outstandingPrincipal = $outstanding['principal'];
            $outstandingInterest = $outstanding['interest'];
            $totalOutstanding = $outstanding['total'];

            $accruedInterest = $overdueSchedules->sum(function ($schedule) {
                return $this->getScheduleOutstandingComponents($schedule)['interest'];
            });

            $requiredProvision = $totalOutstanding * $provisionRate;

            $loanCashSecurities = $cashSecuritiesByLoan->get($loan->id, collect());
            $collateralType = $this->getLoanCollateralType($loan, $loanCashSecurities);
            $forcedSaleValue = $this->getLoanForcedSaleValue($loanCashSecurities);
            $fsvCoverageRatio = $this->getFsvCoverageRatio($forcedSaleValue, $totalOutstanding);
            $primaryDisbursement = $this->getPrimaryDisbursement($loan);
            $disbursementDate = $this->getLoanDisbursementDate($loan, $primaryDisbursement);
            $writeoffFlag = $this->getWriteoffFlag($dpd);

            $writeoffBasis = $writeoffFlag === 'Write-off review'
                ? 'Loan overdue beyond 270 days'
                : '';
            $followUp = $followUpsByLoan->get($loan->id, [
                'count' => 0,
                'has_follow_up' => false,
                'latest_date' => null,
                'latest_outcome' => 'No follow-up',
                'latest_method' => '',
                'latest_by' => '',
                'next_follow_up_date' => null,
                'notes' => '',
                'sms_sent' => false,
            ]);

            $preview[] = [
                'loan_id' => $loan->id,
                'client_id' => $loan->member->code ?? $loan->member->id ?? 'N/A',
                'client_name' => $loan->member->full_name ?? 'Unknown',
                'branch' => $loan->branch->name ?? 'Unknown',
                'field_officer' => $loan->assignedTo->name ?? 'Unassigned',
                'assigned_officer' => $loan->assignedTo->name ?? 'Unassigned',
                'loan_account_no' => $loan->code ?? $loan->id,
                'financing_account_number' => $loan->cash_account_number
                    ?: ($primaryDisbursement->account_number ?? $loan->OLoanID ?? 'N/A'),
                'loan_product' => $loan->product->name ?? 'Unknown',
                'disbursement_date' => $disbursementDate ? $disbursementDate->format('d-M-Y') : 'N/A',
                'disbursement_date_iso' => $disbursementDate ? $disbursementDate->format('Y-m-d') : '',
                'original_principal' => (float) ($loan->principal ?? 0),
                'dpd' => $dpd,
                'overdue_installments' => $risk['overdue_installments'],
                'missed_installments' => $risk['overdue_installments'],
                'classification_basis' => $risk['basis'],
                'classification' => $classification,
                'badge_color' => $badgeColor,
                'outstanding_principal' => $outstandingPrincipal,
                'outstanding_interest' => $outstandingInterest,
                'interest_outstanding' => $outstandingInterest,
                'accrued_interest' => $accruedInterest,
                'total_outstanding' => $totalOutstanding,
                'provision_rate' => number_format($provisionRate * 100, 1) . '%',
                'required_provision' => $requiredProvision,
                'collateral_type' => $collateralType,
                'forced_sale_value' => $forcedSaleValue,
                'fsv_coverage_ratio' => $fsvCoverageRatio,
                'collateral_realized' => $loan->collateral_realized ?? 'Pending',
                'court_recovery_status' => $loan->court_recovery_status ?? 'None',
                'writeoff_basis' => $writeoffBasis,
                'is_restructured' => (bool) ($loan->restructured ?? false),
                'loan_status' => $loan->actual_status,
                'interest_treatment' => $this->getInterestTreatment($classification, $dpd),
                'required_collection_action' => $this->getRequiredCollectionAction($classification, $dpd),
                'writeoff_flag' => $writeoffFlag,
                'follow_up_count' => $followUp['count'],
                'has_follow_up' => $followUp['has_follow_up'],
                'latest_follow_up_date' => $followUp['latest_date'],
                'latest_follow_up_outcome' => $followUp['latest_outcome'],
                'latest_follow_up_method' => $followUp['latest_method'],
                'latest_follow_up_by' => $followUp['latest_by'],
                'next_follow_up_date' => $followUp['next_follow_up_date'],
                'latest_follow_up_notes' => $followUp['notes'],
                'latest_follow_up_sms_sent' => $followUp['sms_sent'],
            ];
        }

        return $preview;
    }

    /**
     * Follow-up summaries keyed by personal loan ID.
     */
    private function getLoanFollowUpSummaries(array $loanIds)
    {
        if (empty($loanIds)) {
            return collect();
        }

        $counts = LoanFollowUp::where('loan_type', 'personal')
            ->whereIn('loan_id', $loanIds)
            ->groupBy('loan_id')
            ->select('loan_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'loan_id');

        $latestIds = LoanFollowUp::where('loan_type', 'personal')
            ->whereIn('loan_id', $loanIds)
            ->groupBy('loan_id')
            ->select(DB::raw('MAX(id) as id'))
            ->pluck('id')
            ->filter()
            ->all();

        if (empty($latestIds)) {
            return collect();
        }

        return LoanFollowUp::with('createdBy:id,name')
            ->whereIn('id', $latestIds)
            ->get()
            ->mapWithKeys(function ($followUp) use ($counts) {
                return [
                    $followUp->loan_id => [
                        'count' => (int) ($counts[$followUp->loan_id] ?? 0),
                        'has_follow_up' => true,
                        'latest_date' => $followUp->follow_up_at?->format('d-M-Y H:i'),
                        'latest_outcome' => ucwords(str_replace('_', ' ', $followUp->outcome)),
                        'latest_method' => ucwords(str_replace('_', ' ', $followUp->contact_method)),
                        'latest_by' => $followUp->createdBy->name ?? 'Staff',
                        'next_follow_up_date' => $followUp->next_follow_up_date?->format('d-M-Y'),
                        'notes' => $followUp->notes ?? '',
                        'sms_sent' => (bool) $followUp->sms_sent,
                    ],
                ];
            });
    }

    /**
     * Best available disbursement row for loan-record reporting.
     */
    private function getPrimaryDisbursement($loan)
    {
        if (!$loan->relationLoaded('disbursements') || $loan->disbursements->isEmpty()) {
            return null;
        }

        return $loan->disbursements
            ->sortByDesc(function ($disbursement) {
                return $disbursement->disbursement_date
                    ?? $disbursement->date_approved
                    ?? $disbursement->created_at;
            })
            ->first();
    }

    /**
     * Best available disbursement date for the loan book.
     */
    private function getLoanDisbursementDate($loan, $primaryDisbursement)
    {
        $dateCandidates = [
            $primaryDisbursement->disbursement_date ?? null,
            $primaryDisbursement->date_approved ?? null,
            $primaryDisbursement->created_at ?? null,
            $loan->date_approved ?? null,
            $loan->datecreated ?? null,
        ];

        foreach ($dateCandidates as $dateCandidate) {
            if (!$dateCandidate) {
                continue;
            }

            return $dateCandidate instanceof Carbon
                ? $dateCandidate
                : Carbon::parse($dateCandidate);
        }

        return null;
    }

    /**
     * Collateral type summary for the loan records register.
     */
    private function getLoanCollateralType($loan, $cashSecurities)
    {
        $types = [];

        $collateralFields = [
            'immovable_assets' => 'Immovable assets',
            'moveable_assets' => 'Moveable assets',
            'intellectual_property' => 'Intellectual property',
            'stocks_collateral' => 'Business stock',
            'livestock_collateral' => 'Livestock',
        ];

        foreach ($collateralFields as $field => $label) {
            if (trim((string) ($loan->{$field} ?? '')) !== '') {
                $types[] = $label;
            }
        }

        if ($cashSecurities->isNotEmpty()) {
            $types[] = 'Cash security';
        }

        return empty($types) ? 'Unsecured' : implode('; ', array_unique($types));
    }

    /**
     * Forced sale value available from captured cash security values.
     */
    private function getLoanForcedSaleValue($cashSecurities)
    {
        $value = (float) $cashSecurities->sum('amount');

        return $value > 0 ? $value : null;
    }

    /**
     * Collateral coverage ratio from forced sale value to total outstanding.
     */
    private function getFsvCoverageRatio($forcedSaleValue, float $totalOutstanding)
    {
        if (!$forcedSaleValue || $totalOutstanding <= 0) {
            return null;
        }

        return round(($forcedSaleValue / $totalOutstanding) * 100, 1);
    }

    /**
     * Interest treatment based on portfolio risk.
     */
    private function getInterestTreatment(string $classification, int $dpd)
    {
        if (in_array($classification, ['Doubtful', 'Loss'], true) || $dpd > 90) {
            return 'Suspend interest';
        }

        return 'Accrue';
    }

    /**
     * Collection action tied to the UMRA risk class.
     */
    private function getRequiredCollectionAction(string $classification, int $dpd)
    {
        if ($dpd > 270) {
            return 'Write-off review and recovery follow-up';
        }

        return match ($classification) {
            'Performing' => 'Routine monitoring',
            'Watch' => 'Client reminder and officer call',
            'Substandard' => 'Field visit and repayment plan',
            'Doubtful' => 'Manager recovery escalation',
            'Loss' => 'Enforcement or write-off review',
            default => 'Routine monitoring',
        };
    }

    /**
     * Flag loans that have crossed the regulatory write-off review trigger.
     */
    private function getWriteoffFlag(int $dpd)
    {
        return $dpd > 270 ? 'Write-off review' : 'No';
    }

    /**
     * Collateral register assembled from loan collateral fields and cash securities.
     */
    private function getCollateralRegister()
    {
        $rows = [];

        $loans = $this->getActiveLoans();
        $loansById = $loans->keyBy('id');
        $activeLoanIds = $this->getActiveLoanIds();

        $collateralFields = [
            'immovable_assets' => 'Immovable assets',
            'moveable_assets' => 'Moveable assets',
            'intellectual_property' => 'Intellectual property',
            'stocks_collateral' => 'Stocks collateral',
            'livestock_collateral' => 'Livestock collateral',
        ];

        foreach ($loans as $loan) {
            foreach ($collateralFields as $field => $label) {
                $description = trim((string) ($loan->{$field} ?? ''));

                if ($description === '') {
                    continue;
                }

                $rows[] = $this->makeCollateralRow($loan, $label, $description, null, 'Loan agreement', 'Registered');
            }
        }

        $cashSecurities = CashSecurity::whereIn('loan_id', $activeLoanIds)
            ->get();

        foreach ($cashSecurities as $cashSecurity) {
            $loan = $loansById->get($cashSecurity->loan_id);

            if (!$loan) {
                continue;
            }

            $status = (int) $cashSecurity->returned === 1
                ? 'Returned'
                : ((int) $cashSecurity->status === 1 ? 'Paid' : 'Pending');

            $rows[] = $this->makeCollateralRow(
                $loan,
                'Cash security',
                $cashSecurity->description ?: 'Cash security deposit',
                $cashSecurity->amount,
                $cashSecurity->transaction_reference ?: $cashSecurity->pay_ref ?: 'Cash security',
                $status
            );
        }

        return collect($rows)
            ->sortBy([
                ['client_name', 'asc'],
                ['loan_account_no', 'asc'],
                ['collateral_type', 'asc'],
            ])
            ->values()
            ->all();
    }

    /**
     * Create one collateral register row.
     */
    private function makeCollateralRow($loan, string $type, string $description, $value, string $source, string $status)
    {
        return [
            'loan_id' => $loan->id,
            'loan_account_no' => $loan->code ?? $loan->id,
            'client_name' => $loan->member->full_name ?? 'Unknown',
            'branch' => $loan->branch->name ?? 'Unknown',
            'assigned_officer' => $loan->assignedTo->name ?? 'Unassigned',
            'collateral_type' => $type,
            'description' => $description,
            'estimated_value' => $value,
            'source' => $source,
            'status' => $status,
        ];
    }

    /**
     * Export dashboard indicators as an Excel workbook.
     */
    public function exportExcel()
    {
        $reportDate = Carbon::now();
        $indicators = $this->getPortfolioIndicators($reportDate);
        $branchSummary = $this->getBranchSummary($reportDate);
        $regulatoryStatus = $this->getRegulatoryReturnStatus();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Portfolio Indicators');

        $indicatorRows = [
            ['UMRA Executive Portfolio Indicators'],
            ['Reporting Date', $indicators['reporting_date']],
            [],
            ['Indicator', 'Value'],
            ['Total Active Loan Accounts', $indicators['total_active_loan_accounts']],
            ['Gross Outstanding Principal (UGX)', $indicators['gross_outstanding_principal']],
            ['Interest Outstanding (UGX)', $indicators['interest_outstanding']],
            ['Required Provision (UGX)', $indicators['required_provision']],
            ['Provision Coverage (%)', $indicators['provision_coverage'] . '%'],
            ['PAR 30 (%)', $indicators['par_30'] . '%'],
            ['PAR 90 (%)', $indicators['par_90'] . '%'],
            ['Loss Classified Exposure (UGX)', $indicators['loss_classified_exposure']],
            ['Write-off Review Accounts', $indicators['writeoff_review_accounts']],
        ];

        $sheet->fromArray($indicatorRows, null, 'A1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A4:B4')->getFont()->setBold(true);

        $branchSheet = $spreadsheet->createSheet();
        $branchSheet->setTitle('Branch Summary');
        $branchSheet->fromArray([[
            'Branch',
            'Active Accounts',
            'Outstanding Principal (UGX)',
            'PAR 30 Exposure (UGX)',
            'PAR 30 %',
            'Provision Required (UGX)',
            'Loss Exposure (UGX)',
        ]], null, 'A1');

        $branchRow = 2;
        foreach ($branchSummary as $branch) {
            $branchSheet->fromArray([[
                $branch['branch'],
                $branch['active_accounts'],
                $branch['outstanding_principal'],
                $branch['par30_exposure'],
                $branch['par30_percent'] / 100,
                $branch['provision_required'],
                $branch['loss_exposure'],
            ]], null, 'A' . $branchRow);
            $branchRow++;
        }

        if ($branchRow > 2) {
            $branchSheet->fromArray([[
                'TOTAL',
                array_sum(array_column($branchSummary, 'active_accounts')),
                array_sum(array_column($branchSummary, 'outstanding_principal')),
                array_sum(array_column($branchSummary, 'par30_exposure')),
                array_sum(array_column($branchSummary, 'outstanding_principal')) > 0
                    ? array_sum(array_column($branchSummary, 'par30_exposure')) / array_sum(array_column($branchSummary, 'outstanding_principal'))
                    : 0,
                array_sum(array_column($branchSummary, 'provision_required')),
                array_sum(array_column($branchSummary, 'loss_exposure')),
            ]], null, 'A' . $branchRow);
        }

        $branchSheet->getStyle('A1:G1')->getFont()->setBold(true);
        $branchSheet->getStyle('E2:E' . max($branchRow, 2))->getNumberFormat()->setFormatCode('0.0%');
        $branchSheet->getStyle('C2:D' . max($branchRow, 2))->getNumberFormat()->setFormatCode('#,##0');
        $branchSheet->getStyle('F2:G' . max($branchRow, 2))->getNumberFormat()->setFormatCode('#,##0');

        if ($branchRow > 2) {
            $branchSheet->getStyle('A' . $branchRow . ':G' . $branchRow)->getFont()->setBold(true);
        }

        foreach (range('A', 'G') as $column) {
            $branchSheet->getColumnDimension($column)->setAutoSize(true);
        }

        $statusSheet = $spreadsheet->createSheet();
        $statusSheet->setTitle('Return Status');
        $statusSheet->fromArray([
            ['Return / Report', 'Regulator / Basis', 'Cadence', 'Workbook Sheet', 'Status'],
        ], null, 'A1');

        $row = 2;
        foreach ($regulatoryStatus as $return) {
            $statusSheet->fromArray([[
                $return['return_name'],
                $return['regulator'],
                $return['cadence'],
                $return['workbook_sheet'],
                $return['status'],
            ]], null, 'A' . $row);
            $row++;
        }

        $statusSheet->getStyle('A1:E1')->getFont()->setBold(true);

        foreach (range('A', 'E') as $column) {
            $statusSheet->getColumnDimension($column)->setAutoSize(true);
        }

        foreach (range('A', 'B') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'UMRA-Dashboard-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

        return response()->streamDownload(function () use ($writer, $spreadsheet) {
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export generated UMRA Schedule 3 risk classification workbook.
     */
    public function exportSchedule3()
    {
        $generatedDate = Carbon::now();
        $riskClassifications = $this->getRiskClassifications();
        $schedule3Summary = $this->getSchedule3Summary($riskClassifications);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Schedule 3');

        $startDate = $generatedDate->copy()->startOfQuarter()->format('d-M-Y');
        $endDate = $generatedDate->copy()->endOfQuarter()->format('d-M-Y');

        $sheet->fromArray([
            ['RISK CLASSIFICATION OF ASSETS AND PROVISIONING'],
            ['Name of Non-Deposit Taking Microfinance Institution', $this->getInstitutionName()],
            ['Financial Year', $generatedDate->format('Y')],
            ['Start Date', $startDate],
            ['End Date', $endDate],
            [],
            ['PORTFOLIO AGEING REPORT'],
            ['No.', 'Classification', 'No. of A/Cs', 'Outstanding Loan Portfolio (UGX)', 'Required Provision', 'Required Provision Amount (UGX)'],
        ], null, 'A1');

        $row = 9;
        $number = 1;

        foreach ($schedule3Summary['standard'] as $summaryRow) {
            $sheet->fromArray([[
                $number++,
                $summaryRow['classification'],
                $summaryRow['accounts'],
                $summaryRow['outstanding'],
                $this->formatPercent($summaryRow['provision_rate']),
                $summaryRow['required_provision'],
            ]], null, 'A' . $row);
            $row++;
        }

        $sheet->fromArray([[
            '',
            'Sub Total',
            $schedule3Summary['standard_total']['accounts'],
            $schedule3Summary['standard_total']['outstanding'],
            '',
            $schedule3Summary['standard_total']['required_provision'],
        ]], null, 'A' . $row);
        $row += 2;

        $sheet->setCellValue('A' . $row, 'Rescheduling or reclassification of loans');
        $row++;
        $number = 6;

        foreach ($schedule3Summary['rescheduled'] as $summaryRow) {
            $sheet->fromArray([[
                $number++,
                $summaryRow['classification'],
                $summaryRow['accounts'],
                $summaryRow['outstanding'],
                $this->formatPercent($summaryRow['provision_rate']),
                $summaryRow['required_provision'],
            ]], null, 'A' . $row);
            $row++;
        }

        $sheet->fromArray([[
            '',
            'Sub Total',
            $schedule3Summary['rescheduled_total']['accounts'],
            $schedule3Summary['rescheduled_total']['outstanding'],
            '',
            $schedule3Summary['rescheduled_total']['required_provision'],
        ]], null, 'A' . $row);
        $row++;

        $sheet->fromArray([[
            '',
            'GRAND TOTAL',
            $schedule3Summary['grand_total']['accounts'],
            $schedule3Summary['grand_total']['outstanding'],
            '',
            $schedule3Summary['grand_total']['required_provision'],
        ]], null, 'A' . $row);

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A7:F8')->getFont()->setBold(true);
        $sheet->getStyle('A' . ($row - 1) . ':F' . $row)->getFont()->setBold(true);

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $detailSheet = $spreadsheet->createSheet();
        $detailSheet->setTitle('Loan Detail');
        $detailSheet->fromArray([[
            'Loan Account',
            'Client Name',
            'Branch',
            'Assigned Officer',
            'Classification',
            'DPD',
            'Overdue Installments',
            'Classification Basis',
            'Outstanding Principal',
            'Outstanding Interest',
            'Total Outstanding',
            'Provision Rate',
            'Required Provision',
            'Rescheduled',
        ]], null, 'A1');

        $detailRow = 2;

        foreach ($riskClassifications as $loans) {
            foreach ($loans as $loan) {
                $outstanding = $this->getLoanOutstandingComponents($loan);
                $outstandingPrincipal = $outstanding['principal'];
                $outstandingInterest = $outstanding['interest'];

                $detailSheet->fromArray([[
                    $loan->code ?? $loan->id,
                    $loan->member->full_name ?? 'N/A',
                    $loan->branch->name ?? 'Unknown',
                    $loan->assignedTo->name ?? 'Unassigned',
                    $loan->umra_classification,
                    $loan->umra_dpd,
                    $loan->umra_overdue_installments,
                    $loan->umra_classification_basis,
                    $outstandingPrincipal,
                    $outstandingInterest,
                    $loan->umra_outstanding_balance,
                    $this->formatPercent($loan->umra_provision_rate),
                    $loan->umra_required_provision,
                    (int) ($loan->restructured ?? 0) === 1 ? 'Yes' : 'No',
                ]], null, 'A' . $detailRow);
                $detailRow++;
            }
        }

        $detailSheet->getStyle('A1:N1')->getFont()->setBold(true);

        foreach (range('A', 'N') as $column) {
            $detailSheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'UMRA-Schedule-3-Risk-Classification-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

        return response()->streamDownload(function () use ($writer, $spreadsheet) {
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export dashboard indicators as a PDF.
     */
    public function exportPdf()
    {
        $reportDate = Carbon::now();
        $indicators = $this->getPortfolioIndicators($reportDate);
        $branchSummary = $this->getBranchSummary($reportDate);
        $regulatoryStatus = $this->getRegulatoryReturnStatus();

        $options = new Options();
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->renderDashboardPdfHtml($indicators, $branchSummary, $regulatoryStatus));
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'UMRA-Dashboard-' . now()->format('Y-m-d-H-i-s') . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Lightweight PDF markup for Dompdf export.
     */
    private function renderDashboardPdfHtml(array $indicators, array $branchSummary, array $regulatoryStatus)
    {
        $rows = '';

        foreach ($indicators as $label => $value) {
            $rows .= '<tr><td>' . $this->formatExportLabel($label) . '</td><td>' . htmlspecialchars((string) $value) . '</td></tr>';
        }

        $branchRows = '';

        foreach ($branchSummary as $branch) {
            $branchRows .= '<tr>'
                . '<td>' . htmlspecialchars($branch['branch']) . '</td>'
                . '<td class="right">' . number_format($branch['active_accounts']) . '</td>'
                . '<td class="right">' . number_format($branch['outstanding_principal'], 0) . '</td>'
                . '<td class="right">' . number_format($branch['par30_exposure'], 0) . '</td>'
                . '<td class="right">' . number_format($branch['par30_percent'], 1) . '%</td>'
                . '<td class="right">' . number_format($branch['provision_required'], 0) . '</td>'
                . '<td class="right">' . number_format($branch['loss_exposure'], 0) . '</td>'
                . '</tr>';
        }

        if ($branchRows === '') {
            $branchRows = '<tr><td colspan="7" class="muted">No active branch portfolio records found.</td></tr>';
        } else {
            $totalPrincipal = array_sum(array_column($branchSummary, 'outstanding_principal'));
            $totalPar30 = array_sum(array_column($branchSummary, 'par30_exposure'));
            $branchRows .= '<tr class="total">'
                . '<td>TOTAL</td>'
                . '<td class="right">' . number_format(array_sum(array_column($branchSummary, 'active_accounts'))) . '</td>'
                . '<td class="right">' . number_format($totalPrincipal, 0) . '</td>'
                . '<td class="right">' . number_format($totalPar30, 0) . '</td>'
                . '<td class="right">' . number_format($totalPrincipal > 0 ? ($totalPar30 / $totalPrincipal) * 100 : 0, 1) . '%</td>'
                . '<td class="right">' . number_format(array_sum(array_column($branchSummary, 'provision_required')), 0) . '</td>'
                . '<td class="right">' . number_format(array_sum(array_column($branchSummary, 'loss_exposure')), 0) . '</td>'
                . '</tr>';
        }

        $statusRows = '';

        foreach ($regulatoryStatus as $return) {
            $statusRows .= '<tr>'
                . '<td>' . htmlspecialchars($return['return_name']) . '</td>'
                . '<td>' . htmlspecialchars($return['regulator']) . '</td>'
                . '<td>' . htmlspecialchars($return['cadence']) . '</td>'
                . '<td>' . htmlspecialchars($return['workbook_sheet']) . '</td>'
                . '<td>' . htmlspecialchars($return['status']) . '</td>'
                . '</tr>';
        }

        return '<!doctype html>
            <html>
            <head>
                <meta charset="utf-8">
                <style>
                    body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10.5px; }
                    h1 { font-size: 20px; margin-bottom: 4px; }
                    h2 { font-size: 14px; margin-top: 24px; }
                    table { border-collapse: collapse; width: 100%; margin-top: 10px; }
                    th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
                    th { background: #111827; color: #ffffff; }
                    .right { text-align: right; }
                    .total td { font-weight: bold; background: #f3f4f6; }
                    .muted { color: #6b7280; margin-top: 0; }
                    .note { font-size: 10px; color: #4b5563; }
                </style>
            </head>
            <body>
                <h1>UMRA Executive Portfolio Indicators</h1>
                <p class="muted">Reporting Date: ' . htmlspecialchars($indicators['reporting_date']) . '</p>
                <p class="note">Basis: active personal loans with unpaid schedules. PAR 30 exposure is outstanding principal for accounts more than 30 days past due. Provision is calculated from UMRA risk class rates.</p>

                <h2>Portfolio Indicators</h2>
                <table>
                    <tbody>' . $rows . '</tbody>
                </table>

                <h2>Branch Summary</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Active Accounts</th>
                            <th>Outstanding Principal (UGX)</th>
                            <th>PAR 30 Exposure (UGX)</th>
                            <th>PAR 30 %</th>
                            <th>Provision Required (UGX)</th>
                            <th>Loss Exposure (UGX)</th>
                        </tr>
                    </thead>
                    <tbody>' . $branchRows . '</tbody>
                </table>

                <h2>Regulatory Return Status</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Return / Report</th>
                            <th>Regulator / Basis</th>
                            <th>Cadence</th>
                            <th>Workbook Sheet</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>' . $statusRows . '</tbody>
                </table>
            </body>
            </html>';
    }

    /**
     * Convert export keys into readable labels.
     */
    private function formatExportLabel(string $key)
    {
        return ucwords(str_replace('_', ' ', $key));
    }

    /**
     * Format decimal rates as whole percentages for UMRA schedules.
     */
    private function formatPercent(float $rate)
    {
        return number_format($rate * 100, 0) . '%';
    }

    /**
     * Institution name for regulatory exports.
     */
    private function getInstitutionName()
    {
        $appName = config('app.name');

        return $appName && $appName !== 'Laravel'
            ? $appName
            : 'Emuria Micro Finance Limited';
    }

    /**
     * Export Preview CSV
     */
    public function exportPreview(Request $request)
    {
        $loanPreview = $this->filterLoanRecords(collect($this->getUmraLoanPreview()), $request);

        $filename = 'UMRA-Loan-Preview-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $output = fopen('php://memory', 'r+');

        fputcsv($output, [
            'Client Name',
            'Client ID',
            'Branch',
            'Field Officer',
            'Loan Account No',
            '(FAN) Financing Account Number',
            'Loan Product',
            'Disbursement Date',
            'Original Principal (UGX)',
            'Outstanding Principal (UGX)',
            'Interest Outstanding (UGX)',
            'Accrued Interest (UGX)',
            '(DPD) Days Past Due',
            'Missed Installments',
            'UMRA Risk Classification',
            'Required Provision Rate',
            'Required Provision Amount (UGX)',
            'Collateral Type',
            '(FSV) Forced Sale Value (UGX)',
            'FSV Coverage Ratio',
            'Loan Status',
            'Interest Treatment',
            'Required Collection Action',
            'Write-off Flag',
            'Follow-up Count',
            'Latest Follow-up Date',
            'Latest Follow-up Outcome',
            'Latest Follow-up Method',
            'Latest Follow-up By',
            'Next Follow-up Date',
            'Latest Follow-up Notes',
            'Follow-up SMS Sent',
        ]);

        foreach ($loanPreview as $loan) {

            fputcsv($output, [
                $loan['client_name'],
                $loan['client_id'],
                $loan['branch'],
                $loan['field_officer'],
                $loan['loan_account_no'],
                $loan['financing_account_number'],
                $loan['loan_product'],
                $loan['disbursement_date'],
                number_format($loan['original_principal'], 2),
                number_format($loan['outstanding_principal'], 2),
                number_format($loan['outstanding_interest'], 2),
                number_format($loan['accrued_interest'], 2),
                $loan['dpd'],
                $loan['missed_installments'],
                $loan['classification'],
                $loan['provision_rate'],
                number_format($loan['required_provision'], 2),
                $loan['collateral_type'],
                $loan['forced_sale_value'] ? number_format($loan['forced_sale_value'], 2) : '',
                $loan['fsv_coverage_ratio'] !== null ? $loan['fsv_coverage_ratio'] . '%' : '',
                $loan['loan_status'],
                $loan['interest_treatment'],
                $loan['required_collection_action'],
                $loan['writeoff_flag'],
                $loan['follow_up_count'],
                $loan['latest_follow_up_date'],
                $loan['latest_follow_up_outcome'],
                $loan['latest_follow_up_method'],
                $loan['latest_follow_up_by'],
                $loan['next_follow_up_date'],
                $loan['latest_follow_up_notes'],
                $loan['latest_follow_up_sms_sent'] ? 'Yes' : 'No',
            ]);
        }

        rewind($output);

        $csv = stream_get_contents($output);

        fclose($output);

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header(
                'Content-Disposition',
                'attachment; filename="' . $filename . '"'
            );
    }

    /**
     * Regulatory Return Status
     */
    private function getRegulatoryReturnStatus()
    {
        return [
            [
                'return_name' => 'Schedule 3: Risk Classification',
                'regulator' => 'UMRA Tier 4 ND-MFI Regulations 2018',
                'cadence' => 'Quarterly',
                'workbook_sheet' => 'Schedule 3',
                'status' => 'Generated',
                'route' => 'admin.umra.schedule3',
            ],
            [
                'return_name' => 'Collateral Register',
                'regulator' => 'UMRA collateral and recovery monitoring',
                'cadence' => 'Monthly',
                'workbook_sheet' => 'Collateral Register',
                'status' => 'Generated',
                'route' => 'admin.umra.collateral-register',
            ],
            [
                'return_name' => 'Loan Records',
                'regulator' => 'UMRA loan book return',
                'cadence' => 'Monthly',
                'workbook_sheet' => 'Loan Records',
                'status' => 'Generated',
                'route' => 'admin.umra.loan-records',
            ],
        ];
    }
}
