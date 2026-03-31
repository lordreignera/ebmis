<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ClientLoanApplication;
use App\Models\Member;
use App\Models\PersonalLoan;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientApplicationController extends Controller
{
    /**
     * List all self-applied loan applications.
     * Tabs: Pending Scoring | Pending FO Review/Verification | All
     */
    public function index(Request $request)
    {
        $tab    = $request->input('tab', 'fo_review');
        $search = $request->input('search');
        $branch = $request->input('branch_id');

        $query = ClientLoanApplication::with(['product', 'branch'])
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('application_code', 'like', "%$search%")
                  ->orWhere('full_name', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%")
                  ->orWhere('national_id', 'like', "%$search%");
            }))
            ->when($branch, fn($q) => $q->where('branch_id', $branch));

        $query = match ($tab) {
            'scoring'   => (clone $query)->where('status', 'pending_scoring'),
            'fo_review' => (clone $query)->whereIn('status', ['pending_fo_review', 'pending_fo_verification']),
            'rejected'  => (clone $query)->where('status', 'rejected'),
            'converted' => (clone $query)->whereIn('status', ['approved', 'converted']),
            default     => $query,
        };

        $applications = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $counts = [
            'scoring'   => ClientLoanApplication::where('status', 'pending_scoring')->count(),
            'fo_review' => ClientLoanApplication::whereIn('status', ['pending_fo_review', 'pending_fo_verification'])->count(),
            'rejected'  => ClientLoanApplication::where('status', 'rejected')->count(),
            'converted' => ClientLoanApplication::whereIn('status', ['approved', 'converted'])->count(),
            'all'       => ClientLoanApplication::count(),
        ];

        $branches = Branch::orderBy('name')->get();

        return view('admin.client-applications.index', compact(
            'applications', 'counts', 'tab', 'search', 'branch', 'branches'
        ));
    }

    /**
     * Show a single application with full scoring report.
     */
    public function show(int $id)
    {
        $app = ClientLoanApplication::with(['product', 'branch', 'reviewer', 'member', 'loan'])
            ->findOrFail($id);

        return view('admin.client-applications.show', compact('app'));
    }

    /**
     * Field officer approves the application.
     * Creates a Member + PersonalLoan record, then redirects to the loan show page
     * so the existing fees → approve → disburse flow takes over.
     */
    public function approve(Request $request, int $id)
    {
        $request->validate([
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        $app = ClientLoanApplication::findOrFail($id);

        if (!in_array($app->status, ['pending_fo_review', 'pending_fo_verification'])) {
            return redirect()->back()->with('error', 'This application cannot be approved in its current status.');
        }

        try {
            DB::beginTransaction();

            // ── Create or locate Member ────────────────────────────────────
            $member = null;
            if ($app->national_id) {
                $member = Member::where('nin', $app->national_id)->first();
            }
            if (!$member && $app->phone) {
                $member = Member::where('contact', $app->phone)->first();
            }
            if (!$member && $app->email) {
                $member = Member::where('email', $app->email)->first();
            }

            if (!$member) {
                // Split name: first word = fname, last word = lname, middle = mname
                $nameParts = explode(' ', trim($app->full_name));
                $fname = $nameParts[0] ?? $app->full_name;
                $lname = count($nameParts) > 1 ? array_pop($nameParts) : '-';
                $mname = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1, -1)) : null;

                $member = Member::create([
                    'code'        => 'PM' . time(),
                    'fname'       => $fname,
                    'lname'       => $lname,
                    'mname'       => $mname,
                    'nin'         => $app->national_id,
                    'contact'     => $app->phone,
                    'email'       => $app->email,
                    'gender'      => $app->gender,
                    'dob'         => $app->date_of_birth,
                    'village'     => $app->residence_village,
                    'parish'      => $app->residence_parish,
                    'subcounty'   => $app->residence_subcounty,
                    'county'      => $app->residence_district,
                    'branch_id'   => $app->branch_id,
                    'member_type' => 1,            // Individual
                    'country_id'  => 1,            // Uganda default
                    'added_by'    => auth()->id(),
                    'verified'    => 1,
                    'status'      => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'approval_notes' => 'Auto-approved via self-application: ' . $app->application_code,
                    'soft_delete' => false,
                ]);
            }

            // ── Determine loan code format ─────────────────────────────────
            $freqCode = strtoupper(substr($app->repayment_frequency, 0, 1));
            $seq      = PersonalLoan::whereDate('datecreated', today())->count() + 1;
            $loanCode = 'P' . $freqCode . 'LOAN' . now()->format('ymdHi') . sprintf('%03d', $seq);

            // ── Calculate installment (flat rate) ──────────────────────────
            $product      = Product::findOrFail($app->product_id);
            $interestRate = (float) $product->interest / 100;
            $principal    = (float) $app->requested_amount;
            $periods      = (int) $app->tenure_periods;
            $total        = $principal + ($principal * $interestRate * $periods);
            $installment  = round($total / $periods, 2);

            // ── Create PersonalLoan (status=0: pending fees + approval) ────
            $loan = PersonalLoan::create([
                'member_id'       => $member->id,
                'product_type'    => $app->product_id,
                'code'            => $loanCode,
                'interest'        => $product->interest,
                'interest_method' => 2,           // declining balance default
                'period'          => $periods,
                'principal'       => $principal,
                'installment'     => $installment,
                'status'          => 0,           // Pending approval
                'verified'        => 0,
                'branch_id'       => $app->branch_id,
                'added_by'        => auth()->id(),
                'repay_strategy'  => 1,
                'repay_name'      => $app->business_name,
                'repay_address'   => $app->business_location,
                'loan_purpose'    => $app->loan_purpose,
                'datecreated'     => now(),
                'sign_code'       => 0,
                'comments'        => 'Self-applied via client portal. App code: ' . $app->application_code
                    . ($request->approval_notes ? ' | FO Notes: ' . $request->approval_notes : ''),
            ]);

            // ── Update the application ─────────────────────────────────────
            $app->update([
                'status'      => 'converted',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'member_id'   => $member->id,
                'loan_id'     => $loan->id,
            ]);

            DB::commit();

            return redirect()->route('admin.loans.show', $loan->id)
                ->with('success',
                    "Application {$app->application_code} approved. Member and loan records created. "
                    . "Loan Code: {$loanCode}. Please collect charge fees then approve the loan."
                );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Client application approval failed: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    /**
     * Field officer rejects the application.
     */
    public function reject(Request $request, int $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $app = ClientLoanApplication::findOrFail($id);

        if ($app->status === 'converted') {
            return redirect()->back()->with('error', 'This application has already been converted to a loan.');
        }

        $app->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'reviewed_by'      => auth()->id(),
            'reviewed_at'      => now(),
        ]);

        return redirect()->route('admin.client-applications.index')
            ->with('success', "Application {$app->application_code} has been rejected.");
    }
}
