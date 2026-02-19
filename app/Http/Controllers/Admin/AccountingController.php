<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\SystemAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Dompdf\Dompdf;
use Dompdf\Options;

class AccountingController extends Controller
{
    /**
     * Display journal entries
     */
    public function journalEntries(Request $request)
    {
        $query = JournalEntry::with(['lines.account', 'costCenter', 'product', 'officer', 'fund'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('journal_number', 'desc');

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        // Filter by reference type
        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('cost_center_id', $request->branch_id);
        }

        // Filter by loan
        if ($request->filled('loan_id')) {
            $query->where(function($q) use ($request) {
                // Direct loan entries (disbursements)
                $q->where(function($subQ) use ($request) {
                    $subQ->whereIn('reference_type', ['Disbursement', 'loan'])
                         ->where('reference_id', $request->loan_id);
                })
                // Repayment entries (need to join with repayments table)
                ->orWhere(function($subQ) use ($request) {
                    $subQ->where('reference_type', 'Repayment')
                         ->whereIn('reference_id', function($query) use ($request) {
                             $query->select('id')
                                   ->from('repayments')
                                   ->where('loan_id', $request->loan_id);
                         });
                });
            });
        }

        $entries = $query->paginate(50);

        // Get loan info if filtering by loan
        $loan = null;
        if ($request->filled('loan_id')) {
            $loan = \App\Models\Loan::find($request->loan_id);
        }

        return view('admin.accounting.journal-entries', compact('entries', 'loan'));
    }

    /**
     * View single journal entry
     */
    public function showJournalEntry(JournalEntry $entry)
    {
        $entry->load(['lines.account', 'costCenter', 'product', 'officer', 'fund']);
        
        return view('admin.accounting.journal-entry-detail', compact('entry'));
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

        $accountCategories = [];
        foreach ($accounts as $a) {
            $accountCategories[$a->Id] = $a->category;
        }

        $balances = $this->calculateBalancesForAccounts($accountCategories, $asOfDate);

        foreach ($accounts as $account) {
            $b = isset($balances[$account->Id]) ? $balances[$account->Id] : 0;
            $account->debit_balance = $b > 0 ? $b : 0;
            $account->credit_balance = $b < 0 ? abs($b) : 0;
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

        // Bulk fetch accounts by category and compute balances in one aggregated query
        $assetAccounts = SystemAccount::where('category', 'Asset')->where('status', 1)->orderBy('code')->get();
        $liabilityAccounts = SystemAccount::where('category', 'Liability')->where('status', 1)->orderBy('code')->get();
        $equityAccounts = SystemAccount::where('category', 'Equity')->where('status', 1)->orderBy('code')->get();

        $allAccounts = $assetAccounts->merge($liabilityAccounts)->merge($equityAccounts);
        $accountCategories = [];
        foreach ($allAccounts as $a) {
            $accountCategories[$a->id] = $a->category;
        }

        $balances = $this->calculateBalancesForAccounts($accountCategories, $asOfDate);

        $assets = [];
        foreach ($assetAccounts as $account) {
            $balance = isset($balances[$account->id]) ? $balances[$account->id] : 0;
            $account->balance = $balance;
            $assets[] = $account;
        }

        $liabilities = [];
        foreach ($liabilityAccounts as $account) {
            $balance = isset($balances[$account->id]) ? abs($balances[$account->id]) : 0;
            $account->balance = $balance;
            $liabilities[] = $account;
        }

        $equity = [];
        foreach ($equityAccounts as $account) {
            $balance = isset($balances[$account->id]) ? abs($balances[$account->id]) : 0;
            $account->balance = $balance;
            $equity[] = $account;
        }

        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance');

        return view('admin.accounting.balance-sheet', compact(
            'assets',
            'liabilities',
            'equity',
            'totalAssets',
            'totalLiabilities',
            'totalEquity',
            'asOfDate'
        ));
    }

    /**
     * Display income statement
     */
    public function incomeStatement(Request $request)
    {
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo = $request->input('date_to', date('Y-m-d'));

        // Bulk compute balances for the period
        $incomeAccounts = SystemAccount::where('category', 'Income')->where('status', 1)->orderBy('code')->get();
        $expenseAccounts = SystemAccount::where('category', 'Expense')->where('status', 1)->orderBy('code')->get();

        $allAccounts = $incomeAccounts->merge($expenseAccounts);
        $accountCategories = [];
        foreach ($allAccounts as $a) {
            $accountCategories[$a->id] = $a->category;
        }

        $periodBalances = $this->calculateBalancesForAccountsForPeriod($accountCategories, $dateFrom, $dateTo);

        $income = [];
        foreach ($incomeAccounts as $account) {
            $account->balance = isset($periodBalances[$account->id]) ? abs($periodBalances[$account->id]) : 0;
            $income[] = $account;
        }

        $expenses = [];
        foreach ($expenseAccounts as $account) {
            $account->balance = isset($periodBalances[$account->id]) ? $periodBalances[$account->id] : 0;
            $expenses[] = $account;
        }

        $totalIncome = array_sum(array_map(function($a){return $a->balance;}, $income));
        $totalExpenses = array_sum(array_map(function($a){return $a->balance;}, $expenses));
        $netIncome = $totalIncome - $totalExpenses;

        return view('admin.accounting.income-statement', compact(
            'income',
            'expenses',
            'totalIncome',
            'totalExpenses',
            'netIncome',
            'dateFrom',
            'dateTo'
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

            // If end date filter is applied, calculate balances as of that date
            // Chart of Accounts shows cumulative balances AS OF a date, not period activity
            if ($endDate) {
                // Build a map of account id => category so we can compute normal balance
                $accountCategories = [];
                foreach ($accounts as $a) {
                    $accountCategories[$a->Id] = $a->category;
                }

                // Fetch aggregated debit/credit sums for all accounts in one query
                $balances = $this->calculateBalancesForAccounts($accountCategories, $endDate);

                // Attach running_balance to each account using the aggregated results
                foreach ($accounts as $account) {
                    $account->running_balance = isset($balances[$account->Id]) ? $balances[$account->Id] : 0;
                }
            }
            // If no dates provided, use the current running_balance from database

            $accounts = $accounts->groupBy('category');

            return view('admin.accounting.chart-of-accounts', compact('accounts', 'startDate', 'endDate'));
            
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

    /**
     * Calculate account balance as of a specific date
     */
    private function calculateAccountBalance($accountId, $asOfDate)
    {
        try {
            $account = SystemAccount::find($accountId);
            if (!$account) return 0;

            // Get all journal lines for this account up to the date
            $debits = JournalLine::whereHas('journalEntry', function ($q) use ($asOfDate) {
                    $q->where('transaction_date', '<=', $asOfDate)
                      ->where('status', 'posted');
                })
                ->where('account_id', $accountId)
                ->sum('debit_amount');

            $credits = JournalLine::whereHas('journalEntry', function ($q) use ($asOfDate) {
                    $q->where('transaction_date', '<=', $asOfDate)
                      ->where('status', 'posted');
                })
                ->where('account_id', $accountId)
                ->sum('credit_amount');

            // Normal balance based on category
            if (in_array($account->category, ['Asset', 'Expense'])) {
                return $debits - $credits; // Debit increases
            } else {
                return $credits - $debits; // Credit increases
            }
        } catch (\Exception $e) {
            \Log::error("Error calculating balance for account {$accountId}: " . $e->getMessage());
            return 0; // Return 0 on error to prevent page crash
        }
    }

    /**
     * Calculate account balance for a period
     */
    private function calculateAccountBalanceForPeriod($accountId, $dateFrom, $dateTo)
    {
        $account = SystemAccount::find($accountId);
        if (!$account) return 0;

        $debits = JournalLine::whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('transaction_date', [$dateFrom, $dateTo])
                  ->where('status', 'posted');
            })
            ->where('account_id', $accountId)
            ->sum('debit_amount');

        $credits = JournalLine::whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('transaction_date', [$dateFrom, $dateTo])
                  ->where('status', 'posted');
            })
            ->where('account_id', $accountId)
            ->sum('credit_amount');

        if (in_array($account->category, ['Asset', 'Expense'])) {
            return $debits - $credits;
        } else {
            return $credits - $debits;
        }
    }

    /**
     * Calculate aggregated balances for multiple accounts as of a specific date.
     * Returns an associative array: [account_id => balance]
     */
    private function calculateBalancesForAccounts(array $accountCategories, $asOfDate)
    {
        if (empty($accountCategories)) return [];

        $accountIds = array_keys($accountCategories);

        $rows = DB::table('journal_lines')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_lines.account_id', $accountIds)
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.transaction_date', '<=', $asOfDate)
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
    private function calculateBalancesForAccountsForPeriod(array $accountCategories, $dateFrom, $dateTo)
    {
        if (empty($accountCategories)) return [];

        $accountIds = array_keys($accountCategories);

        $rows = DB::table('journal_lines')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_lines.account_id', $accountIds)
            ->where('journal_entries.status', 'posted')
            ->whereBetween('journal_entries.transaction_date', [$dateFrom, $dateTo])
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

        $accounts = SystemAccount::where('status', 1)
            ->orderBy('code')
            ->get();

        $data = [];
        $totalDebits = 0;
        $totalCredits = 0;

        // Bulk compute balances for the accounts
        $accountCategories = [];
        foreach ($accounts as $a) {
            $accountCategories[$a->id] = $a->category;
        }

        $balances = $this->calculateBalancesForAccounts($accountCategories, $asOfDate);

        foreach ($accounts as $account) {
            $balance = isset($balances[$account->id]) ? $balances[$account->id] : 0;

            if ($balance != 0) {
                $data[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'category' => $account->category,
                    'debit' => $balance > 0 ? $balance : 0,
                    'credit' => $balance < 0 ? abs($balance) : 0,
                ];

                if ($balance > 0) {
                    $totalDebits += $balance;
                } else {
                    $totalCredits += abs($balance);
                }
            }
        }

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $html = view('admin.accounting.pdf.trial-balance', [
            'data' => $data,
            'totalDebits' => $totalDebits,
            'totalCredits' => $totalCredits,
            'asOfDate' => $asOfDate
        ])->render();
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="trial-balance-' . $asOfDate . '.pdf"'
        ]);
    }

    /**
     * Download Balance Sheet as PDF
     */
    public function downloadBalanceSheet(Request $request)
    {
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));
        $branchId = $request->input('branch_id');

        $assets = [];
        $liabilities = [];
        $equity = [];

        $assetAccounts = SystemAccount::where('category', 'Asset')->where('status', 1)->orderBy('code')->get();
        $liabilityAccounts = SystemAccount::where('category', 'Liability')->where('status', 1)->orderBy('code')->get();
        $equityAccounts = SystemAccount::where('category', 'Equity')->where('status', 1)->orderBy('code')->get();

        $allAccounts = $assetAccounts->merge($liabilityAccounts)->merge($equityAccounts);
        $accountCategories = [];
        foreach ($allAccounts as $a) {
            $accountCategories[$a->id] = $a->category;
        }

        $balances = $this->calculateBalancesForAccounts($accountCategories, $asOfDate);

        $totalAssets = 0;
        foreach ($assetAccounts as $account) {
            $balance = isset($balances[$account->id]) ? $balances[$account->id] : 0;
            if ($balance != 0) {
                $assets[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                $totalAssets += $balance;
            }
        }

        $totalLiabilities = 0;
        foreach ($liabilityAccounts as $account) {
            $balance = isset($balances[$account->id]) ? abs($balances[$account->id]) : 0;
            if ($balance != 0) {
                $liabilities[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                $totalLiabilities += $balance;
            }
        }

        $totalEquity = 0;
        foreach ($equityAccounts as $account) {
            $balance = isset($balances[$account->id]) ? abs($balances[$account->id]) : 0;
            if ($balance != 0) {
                $equity[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                $totalEquity += $balance;
            }
        }

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $html = view('admin.accounting.pdf.balance-sheet', compact(
            'assets', 'liabilities', 'equity', 
            'totalAssets', 'totalLiabilities', 'totalEquity', 'asOfDate'
        ))->render();
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="balance-sheet-' . $asOfDate . '.pdf"'
        ]);
    }

    /**
     * Download Income Statement as PDF
     */
    public function downloadIncomeStatement(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $branchId = $request->input('branch_id');

        $income = [];
        $expenses = [];


        $incomeAccounts = SystemAccount::where('category', 'Income')->where('status', 1)->orderBy('code')->get();
        $expenseAccounts = SystemAccount::where('category', 'Expense')->where('status', 1)->orderBy('code')->get();

        $allAccounts = $incomeAccounts->merge($expenseAccounts);
        $accountCategories = [];
        foreach ($allAccounts as $a) {
            $accountCategories[$a->id] = $a->category;
        }

        $periodBalances = $this->calculateBalancesForAccountsForPeriod($accountCategories, $dateFrom, $dateTo);

        $totalIncome = 0;
        foreach ($incomeAccounts as $account) {
            $balance = isset($periodBalances[$account->id]) ? abs($periodBalances[$account->id]) : 0;
            if ($balance != 0) {
                $income[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                $totalIncome += $balance;
            }
        }

        $totalExpenses = 0;
        foreach ($expenseAccounts as $account) {
            $balance = isset($periodBalances[$account->id]) ? $periodBalances[$account->id] : 0;
            if ($balance != 0) {
                $expenses[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                $totalExpenses += $balance;
            }
        }

        $netIncome = $totalIncome - $totalExpenses;

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $html = view('admin.accounting.pdf.income-statement', compact(
            'income', 'expenses', 'totalIncome', 'totalExpenses', 'netIncome', 'dateFrom', 'dateTo'
        ))->render();
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="income-statement-' . $dateFrom . '-to-' . $dateTo . '.pdf"'
        ]);
    }

    /**
     * Download Journal Entries as PDF
     */
    public function downloadJournalEntries(Request $request)
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

        $entries = $query->get();
        $dateFrom = $request->input('date_from', 'All');
        $dateTo = $request->input('date_to', 'All');

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $html = view('admin.accounting.pdf.journal-entries', compact('entries', 'dateFrom', 'dateTo'))->render();
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="journal-entries-' . now()->format('Y-m-d') . '.pdf"'
        ]);
    }

    /**
     * Download Chart of Accounts as PDF
     */
    public function downloadChartOfAccounts(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date', date('Y-m-d'));

        $accounts = SystemAccount::with('parent')
            ->orderBy('category')
            ->orderBy('code')
            ->orderBy('sub_code')
            ->get();

        // If end date filter is applied, calculate balances as of that date (bulk)
        if ($endDate) {
            $accountCategories = [];
            foreach ($accounts as $a) {
                $accountCategories[$a->Id] = $a->category;
            }

            $balances = $this->calculateBalancesForAccounts($accountCategories, $endDate);

            foreach ($accounts as $account) {
                $account->running_balance = isset($balances[$account->Id]) ? $balances[$account->Id] : 0;
            }
        }

        $accounts = $accounts->groupBy('category');

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $html = view('admin.accounting.pdf.chart-of-accounts', compact('accounts', 'startDate', 'endDate'))->render();
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = 'chart-of-accounts-' . ($endDate ? $endDate : now()->format('Y-m-d')) . '.pdf';
        
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }
}
