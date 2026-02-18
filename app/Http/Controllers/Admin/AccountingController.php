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

        $entries = $query->paginate(50);

        return view('admin.accounting.journal-entries', compact('entries'));
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

        // Get all accounts with their running balances
        $accounts = SystemAccount::with('parent')
            ->orderBy('category')
            ->orderBy('code')
            ->orderBy('sub_code')
            ->get();

        // Calculate balances as of the specified date
        foreach ($accounts as $account) {
            $balance = $this->calculateAccountBalance($account->Id, $asOfDate);
            $account->debit_balance = $balance > 0 ? $balance : 0;
            $account->credit_balance = $balance < 0 ? abs($balance) : 0;
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

        $assets = SystemAccount::where('category', 'Asset')
            ->orderBy('code')
            ->get()
            ->map(function ($account) use ($asOfDate) {
                $account->balance = $this->calculateAccountBalance($account->Id, $asOfDate);
                return $account;
            });

        $liabilities = SystemAccount::where('category', 'Liability')
            ->orderBy('code')
            ->get()
            ->map(function ($account) use ($asOfDate) {
                $account->balance = abs($this->calculateAccountBalance($account->Id, $asOfDate));
                return $account;
            });

        $equity = SystemAccount::where('category', 'Equity')
            ->orderBy('code')
            ->get()
            ->map(function ($account) use ($asOfDate) {
                $account->balance = abs($this->calculateAccountBalance($account->Id, $asOfDate));
                return $account;
            });

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

        $income = SystemAccount::where('category', 'Income')
            ->orderBy('code')
            ->get()
            ->map(function ($account) use ($dateFrom, $dateTo) {
                $account->balance = abs($this->calculateAccountBalanceForPeriod($account->Id, $dateFrom, $dateTo));
                return $account;
            });

        $expenses = SystemAccount::where('category', 'Expense')
            ->orderBy('code')
            ->get()
            ->map(function ($account) use ($dateFrom, $dateTo) {
                $account->balance = $this->calculateAccountBalanceForPeriod($account->Id, $dateFrom, $dateTo);
                return $account;
            });

        $totalIncome = $income->sum('balance');
        $totalExpenses = $expenses->sum('balance');
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
                foreach ($accounts as $account) {
                    $account->running_balance = $this->calculateAccountBalance($account->Id, $endDate);
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

        foreach ($accounts as $account) {
            $balance = $this->calculateAccountBalance($account->id, $asOfDate);
            
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

        $totalAssets = 0;
        foreach ($assetAccounts as $account) {
            $balance = $this->calculateAccountBalance($account->id, $asOfDate);
            if ($balance != 0) {
                $assets[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                $totalAssets += $balance;
            }
        }

        $totalLiabilities = 0;
        foreach ($liabilityAccounts as $account) {
            $balance = abs($this->calculateAccountBalance($account->id, $asOfDate));
            if ($balance != 0) {
                $liabilities[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                $totalLiabilities += $balance;
            }
        }

        $totalEquity = 0;
        foreach ($equityAccounts as $account) {
            $balance = abs($this->calculateAccountBalance($account->id, $asOfDate));
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

        $totalIncome = 0;
        foreach ($incomeAccounts as $account) {
            $balance = abs($this->calculateAccountBalanceForPeriod($account->id, $dateFrom, $dateTo));
            if ($balance != 0) {
                $income[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                $totalIncome += $balance;
            }
        }

        $totalExpenses = 0;
        foreach ($expenseAccounts as $account) {
            $balance = $this->calculateAccountBalanceForPeriod($account->id, $dateFrom, $dateTo);
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

        // If end date filter is applied, calculate balances as of that date
        if ($endDate) {
            foreach ($accounts as $account) {
                $account->running_balance = $this->calculateAccountBalance($account->Id, $endDate);
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
