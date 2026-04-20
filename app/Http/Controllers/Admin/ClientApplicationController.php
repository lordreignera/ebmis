<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ClientLoanApplication;
use App\Models\FieldVerification;
use App\Models\Member;
use App\Models\PersonalLoan;
use App\Models\Product;
use App\Services\ClientLoanScoringService;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientApplicationController extends Controller
{
    public function __construct(private ClientLoanScoringService $scorer) {}

    /**
     * List all self-applied loan applications.
     * Tabs: Pending Scoring | Pending FO Review/Verification | All
     */
    public function index(Request $request)
    {
        $tab    = $request->input('tab', 'fo_verification');
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
            'scoring'          => (clone $query)->where('status', 'pending_scoring'),
            'fo_verification'  => (clone $query)->where('status', 'pending_fo_verification'),
            'fo_review'        => (clone $query)->where('status', 'pending_fo_review'),
            'rejected'         => (clone $query)->where('status', 'rejected'),
            'converted'        => (clone $query)->whereIn('status', ['approved', 'converted']),
            default            => $query,
        };

        $applications = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $counts = [
            'scoring'         => ClientLoanApplication::where('status', 'pending_scoring')->count(),
            'fo_verification' => ClientLoanApplication::where('status', 'pending_fo_verification')->count(),
            'fo_review'       => ClientLoanApplication::where('status', 'pending_fo_review')->count(),
            'rejected'        => ClientLoanApplication::where('status', 'rejected')->count(),
            'converted'       => ClientLoanApplication::whereIn('status', ['approved', 'converted'])->count(),
            'all'             => ClientLoanApplication::count(),
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
        $app = ClientLoanApplication::with(['product', 'branch', 'reviewer', 'member', 'loan', 'fieldVerification.verifier'])
            ->findOrFail($id);

        return view('admin.client-applications.show', compact('app'));
    }

    /**
     * Show the FVL (Field Verification Layer) form for a pending-FO-verification application.
     */
    public function verifyForm(int $id)
    {
        $app = ClientLoanApplication::with(['product', 'branch'])->findOrFail($id);

        if ($app->status !== 'pending_fo_verification') {
            return redirect()->route('admin.client-applications.show', $id)
                ->with('error', 'This application is not awaiting field verification.');
        }

        return view('admin.client-applications.verify', compact('app'));
    }

    /**
     * Process FVL submission: save verification data, trigger scoring, update status.
     */
    public function submitVerification(Request $request, int $id)
    {
        $app = ClientLoanApplication::findOrFail($id);

        if ($app->status !== 'pending_fo_verification') {
            return redirect()->route('admin.client-applications.show', $id)
                ->with('error', 'This application is not awaiting field verification.');
        }

        $request->validate([
            'visit_start'               => 'required|date',
            'visit_end'                 => 'required|date|after_or_equal:visit_start',
            'idv_status'                => 'required|in:Verified,Partially Verified,N/A',
            'pvs_status'                => 'required|in:Verified,Partially Verified,N/A',
            'avs_status'                => 'required|in:Verified,Partially Verified,N/A',
            'next_of_kin_status'        => 'required|in:Verified,Partially Verified,N/A',
            'physical_visit_confirmed'  => 'required|boolean',
            'field_recommendation'      => 'required|in:proceed,flag,reject',
            'v_monthly_sales'           => 'required|numeric|min:0',
            'v_cogs'                    => 'required|numeric|min:0',
            'v_opex'                    => 'required|numeric|min:0',
            'v_household_expenses'      => 'required|numeric|min:0',
            'v_other_income'            => 'nullable|numeric|min:0',
            'v_loan_installment'        => 'nullable|numeric|min:0',
            'officer_notes'             => 'nullable|string|max:2000',
        ]);

        try {
            DB::beginTransaction();

            // Build verified data array
            $fvlData = [
                'application_id'            => $app->id,
                'verified_by'               => auth()->id(),
                'visit_start'               => $request->visit_start,
                'visit_end'                 => $request->visit_end,
                // KYC — status fields (3-state) + legacy booleans kept in sync
                'idv_status'                => $request->idv_status,
                'idv'                       => $request->idv_status === 'Verified',
                'pvs_status'                => $request->pvs_status,
                'pvs'                       => $request->pvs_status === 'Verified',
                'avs_status'                => $request->avs_status,
                'avs'                       => $request->avs_status === 'Verified',
                'next_of_kin_status'        => $request->next_of_kin_status,
                'next_of_kin_v'             => $request->next_of_kin_status === 'Verified',
                'years_residence_v'         => $request->years_residence_v,
                'res_landmark_seen'         => $request->res_landmark_seen,
                'home_door_color_seen'      => $request->home_door_color_seen,
                // GPS / device
                'gps_capture'               => $request->gps_capture,
                'device_id'                 => $request->device_id,
                'on_site_question'          => $request->on_site_question,
                'on_site_answer'            => $request->on_site_answer,
                // Financials verified
                'v_monthly_sales'           => $request->v_monthly_sales,
                'v_cogs'                    => $request->v_cogs,
                'v_opex'                    => $request->v_opex,
                'v_household_expenses'      => $request->v_household_expenses,
                'v_other_income'            => $request->v_other_income ?? 0,
                'v_loan_installment'        => $request->v_loan_installment ?? 0,
                'sales_record_seen'         => (bool) ($request->sales_record_seen ?? false),
                'mobile_money_seen'         => (bool) ($request->mobile_money_seen ?? false),
                'supplier_confirmed'        => (bool) ($request->supplier_confirmed ?? false),
                'supplier_confirmed_name'   => $request->supplier_confirmed_name,
                'business_open_v'           => (bool) ($request->business_open_v ?? false),
                'business_open_days_v'      => $request->business_open_days_v,
                'peak_hours_v'              => $request->peak_hours_v,
                'avg_customers_v'           => $request->avg_customers_v,
                // CRB
                'crb_defaults'              => $request->crb_defaults,
                'crb_arrears'               => $request->crb_arrears,
                'crb_nxt_count'             => $request->crb_nxt_count,
                'crb_ext_inst'              => $request->crb_ext_inst,
                'crb_skip_flag'             => (bool) ($request->crb_skip_flag ?? false),
                // Collateral 1
                'coll_1_vmv'                => $request->coll_1_vmv,
                'coll_1_enc'                => $request->coll_1_enc,
                'coll_1_physically_inspected'  => (bool) ($request->coll_1_physically_inspected ?? false),
                'coll_1_ownership_accepted'    => (bool) ($request->coll_1_ownership_accepted ?? false),
                'coll_1_pledge_signed'         => (bool) ($request->coll_1_pledge_signed ?? false),
                'coll_1_customary_verified'    => (bool) ($request->coll_1_customary_verified ?? false),
                // Collateral 2
                'coll_2_vmv'                => $request->coll_2_vmv,
                'coll_2_enc'                => $request->coll_2_enc,
                'coll_2_physically_inspected'  => (bool) ($request->coll_2_physically_inspected ?? false),
                'coll_2_ownership_accepted'    => (bool) ($request->coll_2_ownership_accepted ?? false),
                'coll_2_pledge_signed'         => (bool) ($request->coll_2_pledge_signed ?? false),
                'coll_2_customary_verified'    => (bool) ($request->coll_2_customary_verified ?? false),
                // Community
                'lc1_name_confirmed'        => (bool) ($request->lc1_name_confirmed ?? false),
                'lc1_contact_confirmed'     => (bool) ($request->lc1_contact_confirmed ?? false),
                'lc1_letter_sighted'        => (bool) ($request->lc1_letter_sighted ?? false),
                'clan_name_confirmed'       => (bool) ($request->clan_name_confirmed ?? false),
                'clan_contact_confirmed'    => (bool) ($request->clan_contact_confirmed ?? false),
                'clan_letter_sighted'       => (bool) ($request->clan_letter_sighted ?? false),
                'ref1_contacted'            => (bool) ($request->ref1_contacted ?? false),
                'ref2_contacted'            => (bool) ($request->ref2_contacted ?? false),
                'ref_consistent_count'      => $request->ref_consistent_count,
                'disputes_reported'         => (bool) ($request->disputes_reported ?? false),
                'residence_stability_evi'   => $request->residence_stability_evi,
                // Guarantor 1
                'g1_contact_verified'       => (bool) ($request->g1_contact_verified ?? false),
                'g1_income_verified'        => $request->g1_income_verified,
                'g1_asset_verified'         => $request->g1_asset_verified,
                'g1_relationship_confirmed' => (bool) ($request->g1_relationship_confirmed ?? false),
                'g1_willing'                => (bool) ($request->g1_willing ?? false),
                'g1_signed'                 => (bool) ($request->g1_signed ?? false),
                // Guarantor 2
                'g2_contact_verified'       => (bool) ($request->g2_contact_verified ?? false),
                'g2_income_verified'        => $request->g2_income_verified,
                'g2_asset_verified'         => $request->g2_asset_verified,
                'g2_relationship_confirmed' => (bool) ($request->g2_relationship_confirmed ?? false),
                'g2_willing'                => (bool) ($request->g2_willing ?? false),
                'g2_signed'                 => (bool) ($request->g2_signed ?? false),
                // Policy
                'contradiction_count'       => $request->contradiction_count ?? 0,
                'time_constraint'           => (bool) ($request->time_constraint ?? false),
                'temp_trigger'              => (bool) ($request->temp_trigger ?? false),
                'field_recommendation'      => $request->field_recommendation,
                'physical_visit_confirmed'  => (bool) $request->physical_visit_confirmed,
                'remote_risk_note'          => $request->remote_risk_note,
                'officer_notes'             => $request->officer_notes,
            ];

            // Handle photo uploads — stored via FileStorageService (Spaces or public/uploads/)
            foreach (['client_home_photo', 'client_business_photo', 'customer_unposed_photo',
                       'officer_selfie_client', 'live_business_stock_photo', 'coll_1_photo', 'coll_2_photo'] as $photoField) {
                if ($request->hasFile($photoField)) {
                    $fvlData[$photoField] = FileStorageService::storeFile($request->file($photoField), 'fvl-photos');
                }
            }

            FieldVerification::create($fvlData);

            // If FO recommends rejection, skip scoring and reject immediately
            if ($request->field_recommendation === 'reject') {
                $app->update([
                    'status'           => 'rejected',
                    'rejection_reason' => 'Field officer rejected after physical visit. Notes: ' . $request->officer_notes,
                    'reviewed_by'      => auth()->id(),
                    'reviewed_at'      => now(),
                ]);
                DB::commit();
                return redirect()->route('admin.client-applications.show', $id)
                    ->with('success', 'Field verification submitted. Application rejected per field officer recommendation.');
            }

            // ── SDL Scoring ───────────────────────────────────────────────
            $scores = $this->scorer->score($app);
            $app->update($scores);
            $app->refresh();

            // ── Auto-convert: DECLINE → rejected, any pass → Member + Loan ─
            if ($scores['final_decision'] === 'DECLINE') {
                $app->update([
                    'status'           => 'rejected',
                    'rejection_reason' => 'System declined after SDL scoring. Traffic light: RED.',
                    'reviewed_by'      => auth()->id(),
                    'reviewed_at'      => now(),
                ]);

                DB::commit();

                return redirect()->route('admin.client-applications.show', $id)
                    ->with('warning', 'Field verification submitted. Application automatically DECLINED by system (SDL score below threshold).');
            }

            // Non-decline: auto-create Member + Loan immediately
            [$member, $loan, $loanCode] = $this->doConvert($app, $request->officer_notes);

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
                    "FVL submitted. System decision: {$scores['final_decision']} (traffic light: {$scores['traffic_light']}). "
                    . "Member and loan auto-created. Loan Code: {$loanCode}. "
                    . "Please collect charge fees then approve the loan."
                );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FVL submission failed: ' . $e->getMessage(), ['app_id' => $id]);
            return redirect()->back()->withInput()
                ->with('error', 'Verification submission failed: ' . $e->getMessage());
        }
    }

    /**
     * Manual approval entry point — kept for edge cases (e.g. re-processing a
     * flagged application). Under normal flow, conversion happens automatically
     * inside submitVerification() after SDL scoring.
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

            [$member, $loan, $loanCode] = $this->doConvert($app, $request->approval_notes);

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

    // ── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Create (or locate) a Member and a PersonalLoan from a ClientLoanApplication.
     * Uses $app->approved_amount when set (ARA decisions), otherwise falls back
     * to $app->requested_amount.
     *
     * Must be called inside an active DB transaction.
     *
     * @return array{0: Member, 1: PersonalLoan, 2: string}  [member, loan, loanCode]
     */
    private function doConvert(ClientLoanApplication $app, ?string $extraNotes = null): array
    {
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
            $nameParts = explode(' ', trim($app->full_name));
            $fname     = $nameParts[0] ?? $app->full_name;
            $lname     = count($nameParts) > 1 ? array_pop($nameParts) : '-';
            $mname     = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1, -1)) : null;

            $member = Member::create([
                'code'           => 'PM' . time(),
                'fname'          => $fname,
                'lname'          => $lname,
                'mname'          => $mname,
                'nin'            => $app->national_id,
                'contact'        => $app->phone,
                'email'          => $app->email,
                'gender'         => $app->gender,
                'dob'            => $app->date_of_birth,
                'village'        => $app->residence_village,
                'parish'         => $app->residence_parish,
                'subcounty'      => $app->residence_subcounty,
                'county'         => $app->residence_district,
                'branch_id'      => $app->branch_id,
                'member_type'    => 1,
                'country_id'     => 1,
                'added_by'       => auth()->id(),
                'verified'       => 1,
                'status'         => 'approved',
                'approved_by'    => auth()->id(),
                'approved_at'    => now(),
                'approval_notes' => 'Auto-approved via self-application: ' . $app->application_code,
                'soft_delete'    => false,
            ]);
        }

        // ── Loan amount: use system-approved amount if set, else requested ─
        $principal = (float) ($app->approved_amount > 0 ? $app->approved_amount : $app->requested_amount);

        // ── Loan code ─────────────────────────────────────────────────────
        $freqCode = strtoupper(substr($app->repayment_frequency, 0, 1));
        $seq      = PersonalLoan::whereDate('datecreated', today())->count() + 1;
        $loanCode = 'P' . $freqCode . 'LOAN' . now()->format('ymdHi') . sprintf('%03d', $seq);

        // ── Installment (flat rate) ───────────────────────────────────────
        $product      = Product::findOrFail($app->product_id);
        $interestRate = (float) $product->interest / 100;
        $periods      = (int) $app->tenure_periods;
        $total        = $principal + ($principal * $interestRate * $periods);
        $installment  = round($total / $periods, 2);

        // ── Create PersonalLoan (status=0: pending fees + approval) ──────
        $loan = PersonalLoan::create([
            'member_id'       => $member->id,
            'product_type'    => $app->product_id,
            'code'            => $loanCode,
            'interest'        => $product->interest,
            'interest_method' => 2,
            'period'          => $periods,
            'principal'       => $principal,
            'installment'     => $installment,
            'status'          => 0,
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
                . ($extraNotes ? ' | Notes: ' . $extraNotes : ''),
        ]);

        return [$member, $loan, $loanCode];
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
