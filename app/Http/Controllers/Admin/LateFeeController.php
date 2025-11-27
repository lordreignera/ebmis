<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LateFee;
use App\Models\PersonalLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LateFeeController extends Controller
{
    /**
     * Display a listing of late fees
     */
    public function index(Request $request)
    {
        $query = LateFee::with(['loan.member', 'schedule', 'member'])
            ->orderBy('calculated_date', 'desc');
        
        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        
        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->where('schedule_due_date', '>=', $request->from_date);
        }
        
        if ($request->has('to_date') && $request->to_date) {
            $query->where('schedule_due_date', '<=', $request->to_date);
        }
        
        // Search by member name or loan code
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('member', function($q) use ($search) {
                $q->where('fname', 'like', "%{$search}%")
                  ->orWhere('lname', 'like', "%{$search}%");
            })->orWhereHas('loan', function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%");
            });
        }
        
        $lateFees = $query->paginate(50);
        
        // Get summary statistics
        $stats = [
            'total' => LateFee::sum('amount'),
            'pending' => LateFee::pending()->sum('amount'),
            'paid' => LateFee::paid()->sum('amount'),
            'waived' => LateFee::waived()->sum('amount'),
            'count' => LateFee::count(),
            'pending_count' => LateFee::pending()->count(),
        ];
        
        return view('admin.late-fees.index', compact('lateFees', 'stats'));
    }
    
    /**
     * Waive a single late fee
     */
    public function waive(Request $request, LateFee $lateFee)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);
        
        if ($lateFee->status != 0) {
            return back()->with('error', 'Only pending late fees can be waived.');
        }
        
        $lateFee->waive($request->reason, auth()->id());
        
        return back()->with('success', 'Late fee waived successfully.');
    }
    
    /**
     * Bulk waive late fees for a date range
     */
    public function bulkWaive(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|string|max:500'
        ]);
        
        $count = LateFee::pending()
            ->whereBetween('schedule_due_date', [$request->from_date, $request->to_date])
            ->count();
        
        if ($count == 0) {
            return back()->with('info', 'No pending late fees found for the selected date range.');
        }
        
        LateFee::pending()
            ->whereBetween('schedule_due_date', [$request->from_date, $request->to_date])
            ->update([
                'status' => 2, // Waived
                'waiver_reason' => $request->reason,
                'waived_at' => now(),
                'waived_by' => auth()->id()
            ]);
        
        return back()->with('success', "Successfully waived {$count} late fees.");
    }
}
