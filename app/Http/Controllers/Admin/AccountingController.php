<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\JournalEntry;
use App\Models\SystemAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Dompdf\Dompdf;
use Dompdf\Options;

class AccountingController extends Controller
{
    /**
     * Display journal entries
     */
    public function journalEntries(Request $request)
    {
        $entries  = $this->buildJournalQuery($request)->paginate(50);
        $loan     = $request->filled('loan_id')     ? \App\Models\Loan::find($request->loan_id)                                  : null;
        $member   = $request->filled('member_id')   ? \App\Models\Member::find($request->member_id)                              : null;
        $investor = $request->filled('investor_id') ? \App\Models\Investor::withCount('investments')->find($request->investor_id) : null;

        return view('admin.accounting.journal-entries', compact('entries', 'loan', 'member', 'investor'));
    }

    /**
     * View single journal entry
     */
    public function showJournalEntry(JournalEntry $entry)
    {
        $entry->load(['lines.account', 'costCenter', 'product', 'officer', 'fund']);
        $relatedReclassEntries = collect();

        if ($entry->reference_type === 'Repayment' && $entry->reference_id) {
            $relatedReclassEntries = JournalEntry::with(['lines.account', 'postedBy'])
                ->where('reference_type', 'Repayment Late Fee Reclass')
                ->where('reference_id', $entry->reference_id)
                ->orderBy('transaction_date')
                ->orderBy('journal_number')
                ->get();
        }
        
        return view('admin.accounting.journal-entry-detail', compact('entry', 'relatedReclassEntries'));
    }

    /**
     * Display trial balance
     */
    public function trialBalance(Request $request)
    {
        $asOfDate = $request->input('as_of_date', date('Y-m-d'));

        // Get all accounts and compute balances in bulk to avoid N+1 queries
        $accounts = SystemAccount::with('parent')
            ->orderBy('category')
            ->orderBy('code')
            ->orderBy('sub_code')
            ->get();

        $balances = $this->calculateBalancesForAccounts($this->buildAccountCategories($accounts), $asOfDate);

        foreach ($accounts as $account) {
            $b = isset($balances[$account->Id]) ? $balances[$account->Id] : 0;
            // Asset & Expense accounts have a natural debit balance;
            // Liability, Income & Equity accounts have a natural credit balance.
            if (in_array($account->category, ['Asset', 'Expense'])) {
                $account->debit_balance  = $b > 0 ? $b : 0;
                $account->credit_balance = $b < 0 ? abs($b) : 0;
            } else {
                $account->credit_balance = $b > 0 ? $b : 0;
                $account->debit_balance  = $b < 0 ? abs($b) : 0;
            }
        }

        // Group by category
        $accountsByCategory = $accounts->groupBy('category');

        // Calculate totals
        $totalDebits = $accounts->sum('debit_balance');
        $totalCredits = $accounts->sum('credit_balance');

        return view('admin.accounting.trial-balance', compact(
            'accountsByCategory',
            'totalDebits',
            'totalCredits',
            'asOfDate'
        ));
    }

    /**
     * Display balance sheet
     */
    public function balanceSheet(Request $request)
    {
        $asOfDate = $request->input('as_of_date', date('Y-m-d'));
        $branchId = $request->input('branch_id') ?: null;
        $branches = Branch::orderBy('name')->get();

        // Bulk fetch accounts by category and compute balances in one aggregated query
        $assetAccounts = SystemAccount::where('category', 'Asset')->where('status', 1)->orderBy('code')->get();
        $liabilityAccounts = SystemAccount::where('category', 'Liability')->where('status', 1)->orderBy('code')->get();
        $equityAccounts = SystemAccount::where('category', 'Equity')->where('status', 1)->orderBy('code')->get();

        $allAccounts = $assetAccounts->merge($liabilityAccounts)->merge($equityAccounts);
        $balances = $this->calculateBalancesForAccounts(
            $this->buildAccountCategories($allAccounts), $asOfDate, $branchId
        );

        $assets = [];
        foreach ($assetAccounts as $account) {
            $balance = isset($balances[$account->Id]) ? $balances[$account->Id] : 0;
            $account->balance = $balance;
            $assets[] = $account;
        }

        $liabilities = [];
        foreach ($liabilityAccounts as $account) {
            $balance = isset($balances[$account->Id]) ? abs($balances[$account->Id]) : 0;
            $account->balance = $balance;
            $liabilities[] = $account;
        }

        $equity = [];
        foreach ($equityAccounts as $account) {
            $balance = isset($balances[$account->Id]) ? abs($balances[$account->Id]) : 0;
            $account->balance = $balance;
            $equity[] = $account;
        }

        $totalAssets      = array_sum(array_column($assets, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
        $totalEquity      = array_sum(array_column($equity, 'balance'));

        $currentYearNetIncome = $this->computeCurrentYearNetIncome($asOfDate, $branchId);
        $totalEquity         += $currentYearNetIncome;

        return view('admin.accounting.balance-sheet', compact(
            'assets',
            'liabilities',
            'equity',
            'totalAssets',
            'totalLiabilities',
            'totalEquity',
            'currentYearNetIncome',
            'asOfDate',
            'branchId',
            'branches'
        ));
    }

    /**
     * Display income statement
     */
    public function incomeStatement(Request $request)
    {
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo = $request->input('date_to', date('Y-m-d'));
        $branchId = $request->input('branch_id') ?: null;
        $branches = Branch::orderBy('name')->get();

        // Bulk compute balances for the period
        $incomeAccounts = SystemAccount::where('category', 'Income')->where('status', 1)->orderBy('code')->get();
        $expenseAccounts = SystemAccount::where('category', 'Expense')->where('status', 1)->orderBy('code')->get();

        $allAccounts = $incomeAccounts->merge($expenseAccounts);
        $periodBalances = $this->calculateBalancesForAccountsForPeriod(
            $this->buildAccountCategories($allAccounts), $dateFrom, $dateTo, $branchId
        );

        $income = [];
        foreach ($incomeAccounts as $account) {
            $account->balance = isset($periodBalances[$account->Id]) ? abs($periodBalances[$account->Id]) : 0;
            $income[] = $account;
        }

        $expenses = [];
        foreach ($expenseAccounts as $account) {
            $account->balance = isset($periodBalances[$account->Id]) ? $periodBalances[$account->Id] : 0;
            $expenses[] = $account;
        }

        $totalIncome   = array_sum(array_column($income,   'balance'));
        $totalExpenses = array_sum(array_column($expenses, 'balance'));
        $netIncome = $totalIncome - $totalExpenses;

        return view('admin.accounting.income-statement', compact(
            'income',
            'expenses',
            'totalIncome',
            'totalExpenses',
            'netIncome',
            'dateFrom',
            'dateTo',
            'branchId',
            'branches'
        ));
    }

    /**
     * Display chart of accounts
     */
    public function chartOfAccounts(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date', date('Y-m-d'));
            $branchId = $request->input('branch_id') ?: null;
            $branches = Branch::orderBy('name')->get();

            // Expand named period shortcuts into concrete start/end dates
            $period = $request->input('period');
            if ($period && $period !== 'custom') {
                $today = now();
                switch ($period) {
                    case 'all':
                        $startDate = null;
                        $endDate   = null;
                        break;
                    case 'current_month':
                        $startDate = $today->copy()->startOfMonth()->format('Y-m-d');
                        $endDate   = $today->format('Y-m-d');
                        break;
                    case 'last_month':
                        $startDate = $today->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                        $endDate   = $today->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                        break;
                    case 'current_quarter':
                        $startDate = $today->copy()->startOfQuarter()->format('Y-m-d');
                        $endDate   = $today->format('Y-m-d');
                        break;
                    case 'last_quarter':
                        $startDate = $today->copy()->subQuarter()->startOfQuarter()->format('Y-m-d');
                        $endDate   = $today->copy()->subQuarter()->endOfQuarter()->format('Y-m-d');
                        break;
                    case 'current_year':
                        $startDate = $today->copy()->startOfYear()->format('Y-m-d');
                        $endDate   = $today->format('Y-m-d');
                        break;
                }
            }

            // Check if system_accounts table exists
            if (!DB::getSchemaBuilder()->hasTable('system_accounts')) {
                return response()->view('errors.custom', [
                    'title' => 'Database Error',
                    'message' => 'System accounts table does not exist. Please run migrations.',
                    'details' => 'Run: php artisan migrate'
                ], 500);
            }

            $accounts = SystemAccount::with('parent')
                ->orderBy('category')
                ->orderBy('code')
                ->orderBy('sub_code')
                ->get();

            $categories = $this->buildAccountCategories($accounts);
            if ($startDate && $endDate) {
                $balances = $this->calculateBalancesForAccountsForPeriod($categories, $startDate, $endDate, $branchId);
            } else {
                $balances = $this->calculateBalancesForAccounts($categories, $endDate ?? date('Y-m-d'), $branchId);
            }
            foreach ($accounts as $account) {
                $account->running_balance = $balances[$account->Id] ?? 0;
            }

            $accounts = $accounts->groupBy('category');

            return view('admin.accounting.chart-of-accounts', compact('accounts', 'startDate', 'endDate', 'branchId', 'branches'));
            
        } catch (\Exception $e) {
            \Log::error('Chart of Accounts Error: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Show detailed error in development, generic in production
            if (config('app.debug')) {
                throw $e;
            }
            
            return response()->view('errors.custom', [
                'title' => 'Error Loading Chart of Accounts',
                'message' => 'An error occurred while loading the chart of accounts.',
                'details' => config('app.debug') ? $e->getMessage() : 'Please contact support.'
            ], 500);
        }
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Build an [account_id => category] map from a collection of SystemAccount models.
     */
    private function buildAccountCategories($collection): array
    {
        $map = [];
        foreach ($collection as $a) {
            $map[$a->Id] = $a->category;
        }
        return $map;
    }

    /**
     * Compute current-year net income (income minus expenses) as of a date.
     * Used by both the web and PDF balance sheet methods.
     */
    private function computeCurrentYearNetIncome(string $asOfDate, ?int $branchId = null): float
    {
        $incomeAccounts  = SystemAccount::where('category', 'Income')->where('status', 1)->get();
        $expenseAccounts = SystemAccount::where('category', 'Expense')->where('status', 1)->get();

        $balances = $this->calculateBalancesForAccounts(
            $this->buildAccountCategories($incomeAccounts->merge($expenseAccounts)),
            $asOfDate, $branchId
        );

        $grossIncome   = $incomeAccounts->sum(fn($a) => $balances[$a->Id] ?? 0);
        $grossExpenses = $expenseAccounts->sum(fn($a) => $balances[$a->Id] ?? 0);
        return $grossIncome - $grossExpenses;
    }

    /**
     * Render a Blade view as a PDF download response.
     */
    private function renderPdf(string $view, array $data, string $filename)
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view($view, $data)->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Build and filter the JournalEntry query from request parameters.
     * Shared between journalEntries() (paginated) and downloadJournalEntries() (full export).
     */
    private function buildJournalQuery(Request $request)
    {
        $query = JournalEntry::with(['lines.account', 'costCenter', 'product', 'officer', 'fund'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('journal_number', 'desc');

        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }
        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('branch_id')) {
            $query->where('cost_center_id', $request->branch_id);
        }

        if ($request->filled('loan_id')) {
            $query->where(function ($q) use ($request) {
                $q->where(fn($s) => $s->where('reference_type', 'loan')->where('reference_id', $request->loan_id))
                  ->orWhere(fn($s) => $s->where('reference_type', 'Disbursement')
                      ->whereIn('reference_id', fn($q) => $q->select('id')->from('disbursements')->where('loan_id', $request->loan_id)))
                  ->orWhere(fn($s) => $s->where('reference_type', 'Repayment')
                      ->whereIn('reference_id', fn($q) => $q->select('id')->from('repayments')->where('loan_id', $request->loan_id)))
                  ->orWhere(fn($s) => $s->whereIn('reference_type', ['Fee Collection', 'Insurance Fee'])
                      ->whereIn('reference_id', fn($q) => $q->select('id')->from('fees')->where('loan_id', $request->loan_id)))
                  ->orWhere(fn($s) => $s->where('reference_type', 'Cash Security')
                      ->whereIn('reference_id', fn($q) => $q->select('id')->from('cash_securities')->where('loan_id', $request->loan_id)));
            });
        }

        if ($request->filled('member_id')) {
            $query->where(function ($q) use ($request) {
                $q->where(fn($s) => $s->where('reference_type', 'Disbursement')
                      ->whereIn('reference_id', fn($q) => $q->select('id')->from('disbursements')
                          ->whereIn('loan_id', fn($lq) => $lq->select('id')->from('loans')->where('member_id', $request->member_id))))
                  ->orWhere(fn($s) => $s->where('reference_type', 'Repayment')
                      ->whereIn('reference_id', fn($q) => $q->select('id')->from('repayments')
                          ->whereIn('loan_id', fn($lq) => $lq->select('id')->from('loans')->where('member_id', $request->member_id))))
                  ->orWhere(fn($s) => $s->whereIn('reference_type', ['Fee Collection', 'Insurance Fee'])
                      ->whereIn('reference_id', fn($q) => $q->select('id')->from('fees')->where('member_id', $request->member_id)))
                  ->orWhere(fn($s) => $s->where('reference_type', 'Cash Security')
                      ->whereIn('reference_id', fn($q) => $q->select('id')->from('cash_securities')->where('member_id', $request->member_id)));
            });
        }

        if ($request->filled('investor_id')) {
            $this->applyInvestorFilter($query, (int) $request->investor_id);
        }

        return $query;
    }

    /**
     * Calculate aggregated balances for multiple accounts as of a specific date.
     * Returns an associative array: [account_id => balance]
     */
    private function calculateBalancesForAccounts(array $accountCategories, $asOfDate, $branchId = null)
    {
        if (empty($accountCategories)) return [];

        $accountIds = array_keys($accountCategories);

        $rows = DB::table('journal_lines')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_lines.account_id', $accountIds)
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.transaction_date', '<=', $asOfDate)
            ->when($branchId, fn($q) => $q->where('journal_entries.cost_center_id', $branchId))
            ->select('journal_lines.account_id', DB::raw('SUM(journal_lines.debit_amount) as debits'), DB::raw('SUM(journal_lines.credit_amount) as credits'))
            ->groupBy('journal_lines.account_id')
            ->get();

        $balances = [];
        foreach ($rows as $r) {
            $acctId = $r->account_id;
            $debits = (float) $r->debits;
            $credits = (float) $r->credits;

            $category = isset($accountCategories[$acctId]) ? $accountCategories[$acctId] : null;
            if (in_array($category, ['Asset', 'Expense'])) {
                $balances[$acctId] = $debits - $credits;
            } else {
                $balances[$acctId] = $credits - $debits;
            }
        }

        return $balances;
    }

    /**
     * Calculate aggregated balances for multiple accounts for a period.
     * Returns an associative array: [account_id => balance]
     */
    private function calculateBalancesForAccountsForPeriod(array $accountCategories, $dateFrom, $dateTo, $branchId = null)
    {
        if (empty($accountCategories)) return [];

        $accountIds = array_keys($accountCategories);

        $rows = DB::table('journal_lines')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_lines.account_id', $accountIds)
            ->where('journal_entries.status', 'posted')
            ->whereBetween('journal_entries.transaction_date', [$dateFrom, $dateTo])
            ->when($branchId, fn($q) => $q->where('journal_entries.cost_center_id', $branchId))
            ->select('journal_lines.account_id', DB::raw('SUM(journal_lines.debit_amount) as debits'), DB::raw('SUM(journal_lines.credit_amount) as credits'))
            ->groupBy('journal_lines.account_id')
            ->get();

        $balances = [];
        foreach ($rows as $r) {
            $acctId = $r->account_id;
            $debits = (float) $r->debits;
            $credits = (float) $r->credits;

            $category = isset($accountCategories[$acctId]) ? $accountCategories[$acctId] : null;
            if (in_array($category, ['Asset', 'Expense'])) {
                $balances[$acctId] = $debits - $credits;
            } else {
                $balances[$acctId] = $credits - $debits;
            }
        }

        return $balances;
    }

    /**
     * Download Trial Balance as PDF
     */
    public function downloadTrialBalance(Request $request)
    {
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));
        $branchId = $request->input('branch_id');

        $accounts = SystemAccount::where('status', 1)->orderBy('code')->get();
        $balances = $this->calculateBalancesForAccounts($this->buildAccountCategories($accounts), $asOfDate);

        $data = [];
        $totalDebits = $totalCredits = 0;
        foreach ($accounts as $account) {
            $balance = $balances[$account->Id] ?? 0;
            if ($balance == 0) continue;

            $isDebitNormal = in_array($account->category, ['Asset', 'Expense']);
            $debit  = $isDebitNormal ? ($balance > 0 ? $balance : 0) : ($balance < 0 ? abs($balance) : 0);
            $credit = $isDebitNormal ? ($balance < 0 ? abs($balance) : 0) : ($balance > 0 ? $balance : 0);

            $data[]        = ['code' => $account->code, 'name' => $account->name, 'category' => $account->category, 'debit' => $debit, 'credit' => $credit];
            $totalDebits  += $debit;
            $totalCredits += $credit;
        }

        return $this->renderPdf(
            'admin.accounting.pdf.trial-balance',
            compact('data', 'totalDebits', 'totalCredits', 'asOfDate'),
            'trial-balance-' . $asOfDate . '.pdf'
        );
    }

    /**
     * Download Balance Sheet as PDF
     */
    public function downloadBalanceSheet(Request $request)
    {
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));
        $branchId = $request->input('branch_id') ?: null;

        $assetAccounts     = SystemAccount::where('category', 'Asset')->where('status', 1)->orderBy('code')->get();
        $liabilityAccounts = SystemAccount::where('category', 'Liability')->where('status', 1)->orderBy('code')->get();
        $equityAccounts    = SystemAccount::where('category', 'Equity')->where('status', 1)->orderBy('code')->get();

        $balances = $this->calculateBalancesForAccounts(
            $this->buildAccountCategories($assetAccounts->merge($liabilityAccounts)->merge($equityAccounts)),
            $asOfDate, $branchId
        );

        $assets = []; $totalAssets = 0;
        foreach ($assetAccounts as $account) {
            if (($balance = $balances[$account->Id] ?? 0) != 0) {
                $assets[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                $totalAssets += $balance;
            }
        }

        $liabilities = []; $totalLiabilities = 0;
        foreach ($liabilityAccounts as $account) {
            if (($balance = isset($balances[$account->Id]) ? abs($balances[$account->Id]) : 0) != 0) {
                $liabilities[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                $totalLiabilities += $balance;
            }
        }

        $equity = []; $totalEquity = 0;
        foreach ($equityAccounts as $account) {
            if (($balance = isset($balances[$account->Id]) ? abs($balances[$account->Id]) : 0) != 0) {
                $equity[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                $totalEquity += $balance;
            }
        }

        $currentYearNetIncome = $this->computeCurrentYearNetIncome($asOfDate, $branchId);
        $totalEquity         += $currentYearNetIncome;

        return $this->renderPdf(
            'admin.accounting.pdf.balance-sheet',
            compact('assets', 'liabilities', 'equity', 'totalAssets', 'totalLiabilities', 'totalEquity', 'currentYearNetIncome', 'asOfDate'),
            'balance-sheet-' . $asOfDate . '.pdf'
        );
    }

    /**
     * Download Income Statement as PDF
     */
    public function downloadIncomeStatement(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->input('date_to', now()->format('Y-m-d'));
        $branchId = $request->input('branch_id') ?: null;

        $incomeAccounts  = SystemAccount::where('category', 'Income')->where('status', 1)->orderBy('code')->get();
        $expenseAccounts = SystemAccount::where('category', 'Expense')->where('status', 1)->orderBy('code')->get();

        $periodBalances = $this->calculateBalancesForAccountsForPeriod(
            $this->buildAccountCategories($incomeAccounts->merge($expenseAccounts)),
            $dateFrom, $dateTo, $branchId
        );

        $income = []; $totalIncome = 0;
        foreach ($incomeAccounts as $account) {
            if (($balance = isset($periodBalances[$account->Id]) ? abs($periodBalances[$account->Id]) : 0) != 0) {
                $income[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                $totalIncome += $balance;
            }
        }

        $expenses = []; $totalExpenses = 0;
        foreach ($expenseAccounts as $account) {
            if (($balance = $periodBalances[$account->Id] ?? 0) != 0) {
                $expenses[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                $totalExpenses += $balance;
            }
        }

        $netIncome = $totalIncome - $totalExpenses;

        return $this->renderPdf(
            'admin.accounting.pdf.income-statement',
            compact('income', 'expenses', 'totalIncome', 'totalExpenses', 'netIncome', 'dateFrom', 'dateTo'),
            'income-statement-' . $dateFrom . '-to-' . $dateTo . '.pdf'
        );
    }

    /**
     * Download Journal Entries as PDF
     */
    public function downloadJournalEntries(Request $request)
    {
        $entries  = $this->buildJournalQuery($request)->get();
        $dateFrom = $request->input('date_from', 'All');
        $dateTo   = $request->input('date_to', 'All');

        return $this->renderPdf(
            'admin.accounting.pdf.journal-entries',
            compact('entries', 'dateFrom', 'dateTo'),
            'journal-entries-' . now()->format('Y-m-d') . '.pdf'
        );
    }

    /**
     * Apply investor-based journal filter with schema-safe disbursement column handling.
     */
    private function applyInvestorFilter($query, int $investorId): void
    {
        $hasInvestmentId = Schema::hasColumn('disbursements', 'investment_id');
        $hasInvId = Schema::hasColumn('disbursements', 'inv_id');

        $query->where(function($q) use ($investorId, $hasInvestmentId, $hasInvId) {
            $q->where(function($subQ) use ($investorId, $hasInvestmentId, $hasInvId) {
                $subQ->where('reference_type', 'Disbursement')
                    ->whereIn('reference_id', function($disbQuery) use ($investorId, $hasInvestmentId, $hasInvId) {
                        $disbQuery->select('id')
                            ->from('disbursements')
                            ->where(function($linkQuery) use ($investorId, $hasInvestmentId, $hasInvId) {
                                $hasAnyLink = false;

                                if ($hasInvestmentId) {
                                    $linkQuery->whereIn('investment_id', function($investmentQuery) use ($investorId) {
                                        $investmentQuery->select('id')
                                            ->from('investment')
                                            ->where('userid', $investorId);
                                    });
                                    $hasAnyLink = true;
                                }

                                if ($hasInvId) {
                                    if ($hasAnyLink) {
                                        $linkQuery->orWhereIn('inv_id', function($investmentQuery) use ($investorId) {
                                            $investmentQuery->select('id')
                                                ->from('investment')
                                                ->where('userid', $investorId);
                                        });
                                    } else {
                                        $linkQuery->whereIn('inv_id', function($investmentQuery) use ($investorId) {
                                            $investmentQuery->select('id')
                                                ->from('investment')
                                                ->where('userid', $investorId);
                                        });
                                    }
                                    $hasAnyLink = true;
                                }

                                if (!$hasAnyLink) {
                                    $linkQuery->whereRaw('1 = 0');
                                }
                            });
                    });
            })
            ->orWhere(function($subQ) use ($investorId) {
                $subQ->whereNotNull('fund_id')
                    ->whereIn('fund_id', function($fundQuery) use ($investorId) {
                        $fundQuery->select('f.id')
                            ->from('funds as f')
                            ->join('investment as i', 'i.name', '=', 'f.name')
                            ->where('i.userid', $investorId);
                    });
            });
        });
    }

    /**
     * Download Chart of Accounts as PDF
     */
    public function downloadChartOfAccounts(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date', date('Y-m-d'));
        $branchId  = $request->input('branch_id') ?: null;

        $accounts = SystemAccount::with('parent')
            ->orderBy('category')
            ->orderBy('code')
            ->orderBy('sub_code')
            ->get();

        $categories = $this->buildAccountCategories($accounts);
        $balances   = $startDate
            ? $this->calculateBalancesForAccountsForPeriod($categories, $startDate, $endDate, $branchId)
            : $this->calculateBalancesForAccounts($categories, $endDate, $branchId);

        foreach ($accounts as $account) {
            $account->running_balance = $balances[$account->Id] ?? 0;
        }

        $accounts = $accounts->groupBy('category');
        $filename = 'chart-of-accounts-' . ($endDate ?: now()->format('Y-m-d')) . '.pdf';

        return $this->renderPdf(
            'admin.accounting.pdf.chart-of-accounts',
            compact('accounts', 'startDate', 'endDate'),
            $filename
        );
    }
}
