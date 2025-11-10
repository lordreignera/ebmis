<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Saving;
use App\Models\Member;
use App\Models\SavingsProduct;
use App\Models\Branch;
use App\Models\SavingTransaction;
use App\Models\SystemAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavingController extends Controller
{
    /**
     * Display a listing of savings accounts
     */
    public function index(Request $request)
    {
        $query = Saving::with(['member', 'product', 'branch', 'addedBy']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('member', function($memberQuery) use ($search) {
                      $memberQuery->where('fname', 'like', "%{$search}%")
                                  ->orWhere('lname', 'like', "%{$search}%")
                                  ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by branch
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by product
        if ($request->has('product_id') && $request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        $savings = $query->orderBy('created_at', 'desc')->paginate(20);

        $branches = Branch::active()->get();
        $products = SavingsProduct::active()->get();

        return view('admin.savings.index', compact('savings', 'branches', 'products'));
    }

    /**
     * Show the form for creating a new savings account
     */
    public function create(Request $request)
    {
        $members = Member::verified()->notDeleted()->get();
        $products = SavingsProduct::active()->get();
        $branches = Branch::active()->get();
        $accounts = SystemAccount::active()->get(); // Add system accounts

        // Pre-select member if passed
        $selectedMember = null;
        if ($request->has('member_id')) {
            $selectedMember = Member::find($request->member_id);
        }

        return view('admin.savings.create', compact('members', 'products', 'branches', 'selectedMember', 'accounts'));
    }

    /**
     * Store a newly created savings account
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'interest' => 'required|numeric|min:0|max:100',
            'initial_deposit' => 'nullable|numeric|min:0',
            'minimum_balance' => 'nullable|numeric|min:0',
            'maximum_balance' => 'nullable|numeric|min:0',
            'auto_dividends' => 'nullable|boolean',
            'charges' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Generate savings account code
            $branch = Branch::find($validated['branch_id']);
            $savingsCount = Saving::where('branch_id', $validated['branch_id'])->count();
            $validated['code'] = 'SAV-' . $branch->name . '-' . str_pad($savingsCount + 1, 6, '0', STR_PAD_LEFT);

            $validated['added_by'] = auth()->id();
            $validated['status'] = 0; // Pending approval
            $validated['balance'] = $validated['initial_deposit'] ?? 0;

            $saving = Saving::create($validated);

            // Record initial deposit if provided
            if (!empty($validated['initial_deposit']) && $validated['initial_deposit'] > 0) {
                SavingTransaction::create([
                    'saving_id' => $saving->id,
                    'type' => 'deposit',
                    'amount' => $validated['initial_deposit'],
                    'balance' => $validated['initial_deposit'],
                    'description' => 'Initial deposit',
                    'added_by' => auth()->id(),
                    'transaction_date' => now()
                ]);
            }

            DB::commit();

            return redirect()->route('admin.savings.show', $saving)
                            ->with('success', 'Savings account created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error creating savings account: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified savings account
     */
    public function show(Saving $saving)
    {
        $saving->load([
            'member.country',
            'member.branch',
            'product',
            'branch',
            'addedBy',
            'transactions.addedBy'
        ]);

        // Calculate statistics
        $stats = [
            'total_deposits' => $saving->transactions()->where('type', 'deposit')->sum('amount'),
            'total_withdrawals' => $saving->transactions()->where('type', 'withdrawal')->sum('amount'),
            'total_interest' => $saving->transactions()->where('type', 'interest')->sum('amount'),
            'transaction_count' => $saving->transactions()->count(),
            'last_transaction' => $saving->transactions()->latest()->first(),
        ];

        return view('admin.savings.show', compact('saving', 'stats'));
    }

    /**
     * Show the form for editing the specified savings account
     */
    public function edit(Saving $saving)
    {
        // Only allow editing of pending accounts
        if ($saving->status !== 0) {
            return redirect()->route('admin.savings.show', $saving)
                            ->with('error', 'Cannot edit approved savings accounts.');
        }

        $members = Member::verified()->notDeleted()->get();
        $products = SavingsProduct::active()->get();
        $branches = Branch::active()->get();

        return view('admin.savings.edit', compact('saving', 'members', 'products', 'branches'));
    }

    /**
     * Update the specified savings account
     */
    public function update(Request $request, Saving $saving)
    {
        // Only allow editing of pending accounts
        if ($saving->status !== 0) {
            return redirect()->route('admin.savings.show', $saving)
                            ->with('error', 'Cannot edit approved savings accounts.');
        }

        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'interest' => 'required|numeric|min:0|max:100',
            'minimum_balance' => 'nullable|numeric|min:0',
            'maximum_balance' => 'nullable|numeric|min:0',
            'auto_dividends' => 'nullable|boolean',
            'charges' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $saving->update($validated);

        return redirect()->route('admin.savings.show', $saving)
                        ->with('success', 'Savings account updated successfully.');
    }

    /**
     * Approve a savings account
     */
    public function approve(Saving $saving)
    {
        if ($saving->status !== 0) {
            return redirect()->back()->with('error', 'Savings account is not in pending status.');
        }

        $saving->update(['status' => 1]); // Approved/Active

        return redirect()->back()->with('success', 'Savings account approved successfully.');
    }

    /**
     * Reject a savings account
     */
    public function reject(Request $request, Saving $saving)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        $saving->update([
            'status' => 2, // Rejected
            'notes' => $request->rejection_reason
        ]);

        return redirect()->back()->with('success', 'Savings account rejected successfully.');
    }

    /**
     * Close a savings account
     */
    public function close(Request $request, Saving $saving)
    {
        $request->validate([
            'closure_reason' => 'required|string|max:500'
        ]);

        if ($saving->balance > 0) {
            return redirect()->back()
                            ->with('error', 'Cannot close account with positive balance. Please withdraw all funds first.');
        }

        $saving->update([
            'status' => 3, // Closed
            'notes' => $request->closure_reason
        ]);

        return redirect()->back()->with('success', 'Savings account closed successfully.');
    }

    /**
     * Process deposit
     */
    public function deposit(Request $request, Saving $saving)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:500',
        ]);

        if ($saving->status !== 1) {
            return redirect()->back()->with('error', 'Can only deposit to active accounts.');
        }

        try {
            DB::beginTransaction();

            $newBalance = $saving->balance + $validated['amount'];

            // Check maximum balance limit
            if ($saving->maximum_balance && $newBalance > $saving->maximum_balance) {
                return redirect()->back()
                                ->with('error', 'Deposit exceeds maximum balance limit of ' . number_format($saving->maximum_balance));
            }

            // Update account balance
            $saving->update(['balance' => $newBalance]);

            // Record transaction
            SavingTransaction::create([
                'saving_id' => $saving->id,
                'type' => 'deposit',
                'amount' => $validated['amount'],
                'balance' => $newBalance,
                'description' => $validated['description'] ?? 'Cash deposit',
                'added_by' => auth()->id(),
                'transaction_date' => now()
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Deposit processed successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                            ->with('error', 'Error processing deposit: ' . $e->getMessage());
        }
    }

    /**
     * Process withdrawal
     */
    public function withdraw(Request $request, Saving $saving)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:500',
        ]);

        if ($saving->status !== 1) {
            return redirect()->back()->with('error', 'Can only withdraw from active accounts.');
        }

        $newBalance = $saving->balance - $validated['amount'];

        // Check minimum balance requirement
        if ($saving->minimum_balance && $newBalance < $saving->minimum_balance) {
            return redirect()->back()
                            ->with('error', 'Withdrawal would result in balance below minimum requirement of ' . number_format($saving->minimum_balance));
        }

        if ($newBalance < 0) {
            return redirect()->back()->with('error', 'Insufficient balance for withdrawal.');
        }

        try {
            DB::beginTransaction();

            // Update account balance
            $saving->update(['balance' => $newBalance]);

            // Record transaction
            SavingTransaction::create([
                'saving_id' => $saving->id,
                'type' => 'withdrawal',
                'amount' => $validated['amount'],
                'balance' => $newBalance,
                'description' => $validated['description'] ?? 'Cash withdrawal',
                'added_by' => auth()->id(),
                'transaction_date' => now()
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Withdrawal processed successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                            ->with('error', 'Error processing withdrawal: ' . $e->getMessage());
        }
    }

    /**
     * Process interest payment
     */
    public function payInterest(Request $request, Saving $saving)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);

        if ($saving->status !== 1) {
            return redirect()->back()->with('error', 'Can only pay interest to active accounts.');
        }

        try {
            DB::beginTransaction();

            $newBalance = $saving->balance + $validated['amount'];

            // Update account balance
            $saving->update(['balance' => $newBalance]);

            // Record transaction
            SavingTransaction::create([
                'saving_id' => $saving->id,
                'type' => 'interest',
                'amount' => $validated['amount'],
                'balance' => $newBalance,
                'description' => $validated['description'] ?? 'Interest payment',
                'added_by' => auth()->id(),
                'transaction_date' => now()
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Interest payment processed successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                            ->with('error', 'Error processing interest payment: ' . $e->getMessage());
        }
    }

    /**
     * Get account balance for AJAX
     */
    public function getBalance(Saving $saving)
    {
        return response()->json([
            'success' => true,
            'balance' => $saving->balance,
            'formatted_balance' => number_format($saving->balance, 2)
        ]);
    }
}