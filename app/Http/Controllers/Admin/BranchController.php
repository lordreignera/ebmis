<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Models\Member;
use App\Models\Loan;
use App\Models\Saving;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BranchController extends Controller
{
    /**
     * Display a listing of branches
     */
    public function index(Request $request)
    {
        $query = Branch::with(['addedBy']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $branches = $query->withCount(['members', 'loans', 'savings', 'groups'])
                         ->orderBy('created_at', 'desc')
                         ->paginate(20);

        return view('admin.branches.index', compact('branches'));
    }

    /**
     * Show the form for creating a new branch
     */
    public function create()
    {
        return view('admin.branches.create');
    }

    /**
     * Store a newly created branch
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager_name' => 'nullable|string|max:255',
            'manager_phone' => 'nullable|string|max:20',
            'status' => 'required|integer|in:0,1',
            'opening_date' => 'nullable|date',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $validated['added_by'] = auth()->id();

        $branch = Branch::create($validated);

        return redirect()->route('admin.branches.show', $branch)
                        ->with('success', 'Branch created successfully.');
    }

    /**
     * Display the specified branch
     */
    public function show(Branch $branch)
    {
        $branch->load(['addedBy']);

        // Get branch statistics
        $stats = [
            'total_members' => $branch->members()->count(),
            'active_members' => $branch->members()->where('status', 1)->count(),
            'total_loans' => $branch->loans()->count(),
            'active_loans' => $branch->loans()->where('status', 2)->count(),
            'total_disbursed' => $branch->loans()->where('status', 2)->sum('principal'),
            'total_savings' => $branch->savings()->where('status', 1)->sum('balance'),
            'total_groups' => $branch->groups()->count(),
            'active_groups' => $branch->groups()->where('status', 1)->count(),
        ];

        // Recent activities
        $recentLoans = $branch->loans()->with('member')->latest()->take(5)->get();
        $recentMembers = $branch->members()->latest()->take(5)->get();
        $recentSavings = $branch->savings()->with('member')->latest()->take(5)->get();

        return view('admin.branches.show', compact('branch', 'stats', 'recentLoans', 'recentMembers', 'recentSavings'));
    }

    /**
     * Show the form for editing the specified branch
     */
    public function edit(Branch $branch)
    {
        return view('admin.branches.edit', compact('branch'));
    }

    /**
     * Update the specified branch
     */
    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code,' . $branch->id,
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager_name' => 'nullable|string|max:255',
            'manager_phone' => 'nullable|string|max:20',
            'status' => 'required|integer|in:0,1',
            'opening_date' => 'nullable|date',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $branch->update($validated);

        return redirect()->route('admin.branches.show', $branch)
                        ->with('success', 'Branch updated successfully.');
    }

    /**
     * Remove the specified branch
     */
    public function destroy(Branch $branch)
    {
        // Check if branch has any data
        $membersCount = $branch->members()->count();
        $loansCount = $branch->loans()->count();
        $savingsCount = $branch->savings()->count();

        if ($membersCount > 0 || $loansCount > 0 || $savingsCount > 0) {
            return redirect()->back()
                            ->with('error', 'Cannot delete branch. It has associated members, loans, or savings accounts.');
        }

        $branch->delete();

        return redirect()->route('admin.branches.index')
                        ->with('success', 'Branch deleted successfully.');
    }

    /**
     * Toggle branch status
     */
    public function toggleStatus(Branch $branch)
    {
        $branch->update([
            'status' => $branch->status === 1 ? 0 : 1
        ]);

        $status = $branch->status === 1 ? 'activated' : 'deactivated';

        return redirect()->back()
                        ->with('success', "Branch {$status} successfully.");
    }

    /**
     * Get branch performance report
     */
    public function performanceReport(Branch $branch, Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth();
        $endDate = $request->end_date ?? now()->endOfMonth();

        // Loan performance
        $loanStats = [
            'applications' => $branch->loans()
                                   ->whereBetween('created_at', [$startDate, $endDate])
                                   ->count(),
            'approvals' => $branch->loans()
                                ->whereBetween('created_at', [$startDate, $endDate])
                                ->where('status', '>=', 1)
                                ->count(),
            'disbursements' => $branch->loans()
                                    ->whereBetween('created_at', [$startDate, $endDate])
                                    ->where('status', 2)
                                    ->sum('principal'),
            'repayments' => $branch->loans()
                                 ->with('repayments')
                                 ->get()
                                 ->flatMap->repayments
                                 ->whereBetween('date', [$startDate, $endDate])
                                 ->sum('amount'),
        ];

        // Savings performance
        $savingsStats = [
            'new_accounts' => $branch->savings()
                                   ->whereBetween('created_at', [$startDate, $endDate])
                                   ->count(),
            'total_deposits' => $branch->savings()
                                     ->with('transactions')
                                     ->get()
                                     ->flatMap->transactions
                                     ->where('type', 'deposit')
                                     ->whereBetween('transaction_date', [$startDate, $endDate])
                                     ->sum('amount'),
            'total_withdrawals' => $branch->savings()
                                        ->with('transactions')
                                        ->get()
                                        ->flatMap->transactions
                                        ->where('type', 'withdrawal')
                                        ->whereBetween('transaction_date', [$startDate, $endDate])
                                        ->sum('amount'),
        ];

        // Member performance
        $memberStats = [
            'new_members' => $branch->members()
                                  ->whereBetween('created_at', [$startDate, $endDate])
                                  ->count(),
            'total_members' => $branch->members()->count(),
            'active_members' => $branch->members()->where('status', 1)->count(),
        ];

        return view('admin.branches.performance', compact(
            'branch', 'loanStats', 'savingsStats', 'memberStats', 'startDate', 'endDate'
        ));
    }
}