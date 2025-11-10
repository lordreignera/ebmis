<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvestmentController extends Controller
{
    /**
     * Display a listing of investments
     */
    public function index(Request $request)
    {
        $query = Investment::with(['investor.country']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhereHas('investor', function($inv) use ($search) {
                      $inv->where('fname', 'like', "%{$search}%")
                          ->orWhere('lname', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        $investments = $query->orderBy('created_at', 'desc')->paginate(20);

        // Calculate statistics
        $stats = [
            'total_investments' => Investment::count(),
            'active_investments' => Investment::active()->count(),
            'total_amount' => Investment::sum(DB::raw('CAST(amount AS DECIMAL(15,2))')),
            'total_interest' => Investment::sum(DB::raw('CAST(interest AS DECIMAL(15,2))')),
            'pending_investments' => Investment::pending()->count(),
        ];

        return view('admin.investments.index', compact('investments', 'stats'));
    }

    /**
     * Display a listing of investors
     */
    public function investors(Request $request)
    {
        $type = $request->get('type', 'all'); // all, local, international

        $query = Investor::with(['country', 'investments'])
                         ->notDeleted()
                         ->withCount('investments');

        // Filter by investor type
        if ($type === 'local') {
            $query->local();
        } elseif ($type === 'international') {
            $query->international();
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('fname', 'like', "%{$search}%")
                  ->orWhere('lname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $investors = $query->orderBy('created_at', 'desc')->paginate(20);

        // Calculate statistics
        $stats = [
            'total_investors' => Investor::notDeleted()->count(),
            'international_investors' => Investor::international()->notDeleted()->count(),
            'local_investors' => Investor::local()->notDeleted()->count(),
            'active_investors' => Investor::active()->notDeleted()->count(),
            'total_investment_value' => Investment::sum(DB::raw('CAST(amount AS DECIMAL(15,2))')),
        ];

        return view('admin.investments.investors', compact('investors', 'stats', 'type'));
    }

    /**
     * Show the form for creating a new investor
     */
    public function createInvestor()
    {
        $countries = Country::orderBy('name')->get();
        
        return view('admin.investments.create-investor', compact('countries'));
    }

    /**
     * Store a newly created investor
     */
    public function storeInvestor(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|integer|in:1,2,3,4,5',
            'fname' => 'required|string|max:200',
            'lname' => 'required|string|max:200',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:500',
            'country' => 'required|exists:countries,id',
            'zip' => 'nullable|string|max:100',
            'email' => 'required|email|max:500|unique:investors',
            'phone' => 'required|string|max:100',
            'gender' => 'required|string|in:Male,Female',
            'IDtype' => 'required|string|max:500',
            'IDnumber' => 'required|string|max:500',
            'description' => 'nullable|string',
            'dob' => 'required|date'
        ]);

        // Generate passcode
        $validated['passcode'] = rand(100000, 999999);
        $validated['status'] = 1; // Active by default

        $investor = Investor::create($validated);

        return redirect()->route('admin.investments.investors')
                        ->with('success', 'Investor created successfully.');
    }

    /**
     * Show the form for adding investment to investor
     */
    public function createInvestment($investorId)
    {
        $investor = Investor::findOrFail($investorId);
        
        return view('admin.investments.create-investment', compact('investor'));
    }

    /**
     * Store a newly created investment
     */
    public function storeInvestment(Request $request, $investorId)
    {
        $investor = Investor::findOrFail($investorId);

        $validated = $request->validate([
            'inv_name' => 'required|string|max:200',
            'inv_amt' => 'required|numeric|min:1000',
            'inv_period' => 'required|integer|min:1|max:7',
            'inv_term' => 'required|integer|in:1,2',
            'areas' => 'required|array',
            'details' => 'required|string'
        ]);

        // Calculate investment details
        $calculation = Investment::calculateInterest(
            $validated['inv_amt'],
            $validated['inv_period'],
            $validated['inv_term']
        );

        // Create start and end dates
        $startDate = now();
        $endDate = $startDate->copy()->addYears($validated['inv_period']);

        $investmentData = [
            'userid' => $investor->id,
            'type' => $validated['inv_term'],
            'name' => $validated['inv_name'],
            'amount' => $validated['inv_amt'],
            'period' => $validated['inv_period'],
            'percentage' => $calculation['rate'],
            'start' => $startDate->format('m/d/Y'),
            'end' => $endDate->format('m/d/Y'),
            'interest' => $calculation['total_interest'],
            'details' => $validated['details'],
            'status' => 1,
            'added_by' => auth()->user()->name
        ];

        Investment::create($investmentData);

        // Store areas of interest
        foreach ($validated['areas'] as $area) {
            DB::table('areas_of_interest')->insert([
                'inv_id' => $investor->id,
                'area' => $area,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return redirect()->route('admin.investments.show-investor', $investor->id)
                        ->with('success', 'Investment created successfully.');
    }

    /**
     * Display the specified investor
     */
    public function showInvestor(Investor $investor)
    {
        $investor->load(['country', 'investments']);
        
        $stats = [
            'total_investments' => $investor->investments->count(),
            'active_investments' => $investor->activeInvestments->count(),
            'total_invested' => $investor->total_investment,
            'total_returns' => $investor->investments->sum(function($inv) {
                return (float)$inv->amount + (float)$inv->interest;
            })
        ];

        return view('admin.investments.show-investor', compact('investor', 'stats'));
    }

    /**
     * Show the investment details
     */
    public function showInvestment(Investment $investment)
    {
        $investment->load(['investor.country']);
        
        return view('admin.investments.show-investment', compact('investment'));
    }

    /**
     * Calculate investment returns (AJAX)
     */
    public function calculateReturns(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'period' => 'required|integer|min:1|max:7',
            'type' => 'required|integer|in:1,2'
        ]);

        $calculation = Investment::calculateInterest(
            $request->amount,
            $request->period,
            $request->type
        );

        // Calculate fees
        $conversionFee = $request->amount * 0.005;
        $totalCharge = $request->amount + $conversionFee;

        // Calculate dates
        $startDate = now();
        $endDate = $startDate->copy()->addYears($request->period);

        return response()->json([
            'type_name' => $request->type == 1 ? 'Standard Interest' : 'Compound Interest',
            'period' => $request->period . ' years',
            'rate' => $calculation['rate'] . '%',
            'annual_profit' => $calculation['annual_profit'],
            'total_interest' => $calculation['total_interest'],
            'total_return' => $calculation['total_return'],
            'conversion_fee' => $conversionFee,
            'total_charge' => $totalCharge,
            'start_date' => $startDate->format('F d, Y'),
            'end_date' => $endDate->format('F d, Y'),
            'formatted' => [
                'amount' => '$' . number_format($request->amount, 2),
                'annual_profit' => '$' . number_format($calculation['annual_profit'], 2),
                'total_interest' => '$' . number_format($calculation['total_interest'], 2),
                'total_return' => '$' . number_format($calculation['total_return'], 2),
                'conversion_fee' => '$' . number_format($conversionFee, 2),
                'total_charge' => '$' . number_format($totalCharge, 2)
            ]
        ]);
    }

    /**
     * Delete investor
     */
    public function deleteInvestor(Request $request, Investor $investor)
    {
        $request->validate([
            'code' => 'required|string',
            'del_comments' => 'required|string|max:100'
        ]);

        // Verify admin security code (you may want to implement proper verification)
        // For now, just check if code is provided

        $investor->update([
            'soft_delete' => 1,
            'del_user' => auth()->id(),
            'del_comments' => $request->del_comments
        ]);

        return redirect()->route('admin.investments.investors')
                        ->with('success', 'Investor deactivated successfully.');
    }
}