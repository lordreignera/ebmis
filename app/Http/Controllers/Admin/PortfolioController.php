<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortfolioController extends Controller
{
    public function running(Request $request)
    {
        $query = Loan::with(['member', 'product', 'branch'])
                     ->where('status', 'disbursed')
                     ->where('outstanding_amount', '>', 0);

        // Apply filters
        $this->applyFilters($query, $request);

        $loans = $query->paginate(20);
        $stats = $this->getRunningLoansStats();

        return view('admin.portfolio.running', compact('loans', 'stats'));
    }

    public function pending(Request $request)
    {
        $query = Loan::with(['member', 'product', 'branch'])
                     ->whereIn('status', ['pending', 'approved']);

        // Apply filters
        $this->applyFilters($query, $request);

        $loans = $query->paginate(20);
        $stats = $this->getPendingLoansStats();

        return view('admin.portfolio.pending', compact('loans', 'stats'));
    }

    public function overdue(Request $request)
    {
        $query = Loan::with(['member', 'product', 'branch'])
                     ->where('status', 'disbursed')
                     ->where('due_date', '<', now())
                     ->where('outstanding_amount', '>', 0);

        // Apply filters
        $this->applyFilters($query, $request);

        $loans = $query->paginate(20);
        $stats = $this->getOverdueLoansStats();

        return view('admin.portfolio.overdue', compact('loans', 'stats'));
    }

    public function paid(Request $request)
    {
        $query = Loan::with(['member', 'product', 'branch'])
                     ->where('status', 'paid');

        // Apply filters
        $this->applyFilters($query, $request);

        $loans = $query->paginate(20);
        $stats = $this->getPaidLoansStats();

        return view('admin.portfolio.paid', compact('loans', 'stats'));
    }

    public function bad(Request $request)
    {
        $query = Loan::with(['member', 'product', 'branch'])
                     ->whereIn('status', ['default', 'written_off']);

        // Apply filters
        $this->applyFilters($query, $request);

        $loans = $query->paginate(20);
        $stats = $this->getBadLoansStats();

        return view('admin.portfolio.bad', compact('loans', 'stats'));
    }

    public function branch(Request $request)
    {
        $branches = Branch::with(['loans' => function($query) {
            $query->select('branch_id', 'status', 'loan_amount', 'outstanding_amount', 'paid_amount');
        }])->get();

        $branchStats = [];
        foreach ($branches as $branch) {
            $branchStats[] = [
                'branch' => $branch,
                'total_loans' => $branch->loans->count(),
                'disbursed_amount' => $branch->loans->sum('loan_amount'),
                'outstanding_amount' => $branch->loans->sum('outstanding_amount'),
                'paid_amount' => $branch->loans->sum('paid_amount'),
                'running_loans' => $branch->loans->where('status', 'disbursed')->where('outstanding_amount', '>', 0)->count(),
                'overdue_loans' => $branch->loans->where('status', 'disbursed')->where('due_date', '<', now())->where('outstanding_amount', '>', 0)->count(),
            ];
        }

        return view('admin.portfolio.branch', compact('branchStats'));
    }

    public function product(Request $request)
    {
        $products = Product::loanProducts()->with(['loans' => function($query) {
            $query->select('loan_product_id', 'status', 'loan_amount', 'outstanding_amount', 'paid_amount');
        }])->get();

        $productStats = [];
        foreach ($products as $product) {
            $productStats[] = [
                'product' => $product,
                'total_loans' => $product->loans->count(),
                'disbursed_amount' => $product->loans->sum('loan_amount'),
                'outstanding_amount' => $product->loans->sum('outstanding_amount'),
                'paid_amount' => $product->loans->sum('paid_amount'),
                'running_loans' => $product->loans->where('status', 'disbursed')->where('outstanding_amount', '>', 0)->count(),
                'overdue_loans' => $product->loans->where('status', 'disbursed')->where('due_date', '<', now())->where('outstanding_amount', '>', 0)->count(),
            ];
        }

        return view('admin.portfolio.product', compact('productStats'));
    }

    public function individual(Request $request)
    {
        $query = Loan::with(['member', 'product', 'branch'])
                     ->where('loan_type', 'individual');

        // Apply filters
        $this->applyFilters($query, $request);

        $loans = $query->paginate(20);
        $stats = $this->getIndividualLoansStats();

        return view('admin.portfolio.individual', compact('loans', 'stats'));
    }

    public function group(Request $request)
    {
        $query = Loan::with(['member', 'product', 'branch'])
                     ->where('loan_type', 'group');

        // Apply filters
        $this->applyFilters($query, $request);

        $loans = $query->paginate(20);
        $stats = $this->getGroupLoansStats();

        return view('admin.portfolio.group', compact('loans', 'stats'));
    }

    private function applyFilters($query, Request $request)
    {
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('loan_id', 'LIKE', "%{$search}%")
                  ->orWhereHas('member', function ($q2) use ($search) {
                      $q2->where('first_name', 'LIKE', "%{$search}%")
                         ->orWhere('last_name', 'LIKE', "%{$search}%")
                         ->orWhere('member_id', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Date range filtering
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('disbursed_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('disbursed_at', '<=', $request->end_date);
        }

        // Branch filtering
        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }

        // Product filtering
        if ($request->has('product_id') && !empty($request->product_id)) {
            $query->where('loan_product_id', $request->product_id);
        }
    }

    private function getRunningLoansStats()
    {
        return [
            'total_loans' => Loan::where('status', 'disbursed')->where('outstanding_amount', '>', 0)->count(),
            'total_amount' => Loan::where('status', 'disbursed')->where('outstanding_amount', '>', 0)->sum('loan_amount'),
            'outstanding_amount' => Loan::where('status', 'disbursed')->sum('outstanding_amount'),
            'paid_amount' => Loan::where('status', 'disbursed')->sum('paid_amount'),
        ];
    }

    private function getPendingLoansStats()
    {
        return [
            'total_pending' => Loan::whereIn('status', ['pending', 'approved'])->count(),
            'pending_amount' => Loan::whereIn('status', ['pending', 'approved'])->sum('loan_amount'),
            'today_applications' => Loan::whereIn('status', ['pending', 'approved'])->whereDate('created_at', today())->count(),
        ];
    }

    private function getOverdueLoansStats()
    {
        return [
            'total_overdue' => Loan::where('status', 'disbursed')->where('due_date', '<', now())->where('outstanding_amount', '>', 0)->count(),
            'overdue_amount' => Loan::where('status', 'disbursed')->where('due_date', '<', now())->sum('outstanding_amount'),
            'average_overdue_days' => Loan::where('status', 'disbursed')->where('due_date', '<', now())->where('outstanding_amount', '>', 0)->avg(DB::raw('DATEDIFF(NOW(), due_date)')),
        ];
    }

    private function getPaidLoansStats()
    {
        return [
            'total_paid' => Loan::where('status', 'paid')->count(),
            'total_paid_amount' => Loan::where('status', 'paid')->sum('loan_amount'),
            'average_payment_period' => Loan::where('status', 'paid')->avg(DB::raw('DATEDIFF(paid_date, disbursed_at)')),
        ];
    }

    private function getBadLoansStats()
    {
        return [
            'total_bad' => Loan::whereIn('status', ['default', 'written_off'])->count(),
            'bad_debt_amount' => Loan::whereIn('status', ['default', 'written_off'])->sum('outstanding_amount'),
            'written_off_amount' => Loan::where('status', 'written_off')->sum('outstanding_amount'),
        ];
    }

    private function getIndividualLoansStats()
    {
        return [
            'total_individual' => Loan::where('loan_type', 'individual')->count(),
            'individual_amount' => Loan::where('loan_type', 'individual')->sum('loan_amount'),
            'individual_outstanding' => Loan::where('loan_type', 'individual')->sum('outstanding_amount'),
        ];
    }

    private function getGroupLoansStats()
    {
        return [
            'total_group' => Loan::where('loan_type', 'group')->count(),
            'group_amount' => Loan::where('loan_type', 'group')->sum('loan_amount'),
            'group_outstanding' => Loan::where('loan_type', 'group')->sum('outstanding_amount'),
        ];
    }
}