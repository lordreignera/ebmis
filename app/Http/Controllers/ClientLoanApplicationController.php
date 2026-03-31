<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ClientLoanApplication;
use App\Models\Member;
use App\Models\PersonalLoan;
use App\Models\Product;
use App\Services\ClientLoanScoringService;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClientLoanApplicationController extends Controller
{
    public function __construct(private ClientLoanScoringService $scorer) {}

    /**
     * Show the public self-service application form.
     */
    public function create()
    {
        $products = Product::where('isactive', 1)->where('loan_type', 1)->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();

        return view('client.apply', compact('products', 'branches'));
    }

    /**
     * Store the application, run scoring, and redirect with the generated code.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Applicant
            'full_name'                  => 'required|string|max:200',
            'phone'                      => 'required|string|max:20',
            'email'                      => 'nullable|email|max:150',
            'national_id'                => 'nullable|string|max:30',
            'date_of_birth'              => 'nullable|date|before:today',
            'gender'                     => 'nullable|in:Male,Female,Other',
            'branch_id'                  => 'required|exists:branches,id',
            // Loan
            'product_id'                 => 'required|exists:products,id',
            'requested_amount'           => 'required|numeric|min:100000',
            'tenure_periods'             => 'required|integer|min:1|max:104',
            'repayment_frequency'        => 'required|in:daily,weekly,monthly',
            'loan_purpose'               => 'nullable|string|max:500',
            'preferred_disbursement_method' => 'nullable|string|max:50',
            // Residence
            'residence_village'          => 'nullable|string|max:100',
            'residence_parish'           => 'nullable|string|max:100',
            'residence_subcounty'        => 'nullable|string|max:100',
            'residence_district'         => 'nullable|string|max:100',
            'landmark_directions'        => 'nullable|string|max:500',
            'years_at_residence'         => 'nullable|integer|min:0|max:100',
            // LC1
            'lc1_name'                   => 'nullable|string|max:150',
            'lc1_phone'                  => 'nullable|string|max:20',
            'has_local_reference'        => 'nullable|boolean',
            'reference_name'             => 'nullable|string|max:150',
            'reference_phone'            => 'nullable|string|max:20',
            'reference_relationship'     => 'nullable|string|max:100',
            // Business
            'business_name'              => 'required|string|max:200',
            'business_type'              => 'nullable|string|max:100',
            'business_location'          => 'nullable|string|max:200',
            'business_years_operation'   => 'nullable|integer|min:0|max:100',
            'business_description'       => 'nullable|string|max:1000',
            'avg_daily_customers'        => 'nullable|integer|min:0',
            // Documents (optional uploads)
            'business_profile_photo'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'business_activity_photos'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'inventory_photos'           => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'sales_book_photo'           => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'purchases_book_photo'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'expense_records_photo'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'mobile_money_statements'    => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            // Financial
            'daily_sales_claimed'        => 'required|numeric|min:0',
            'business_expenses_claimed'  => 'required|numeric|min:0',
            'household_expenses_claimed' => 'required|numeric|min:0',
            'other_income_claimed'       => 'nullable|numeric|min:0',
            'has_external_loans'         => 'nullable|boolean',
            'external_lenders_count'     => 'nullable|integer|min:0',
            'external_outstanding'       => 'nullable|numeric|min:0',
            'external_installment_per_period' => 'nullable|numeric|min:0',
            'max_external_arrears_days'  => 'nullable|integer|min:0',
            // Collateral 1
            'collateral_1_type'          => 'required|string|max:100',
            'collateral_1_description'   => 'required|string|max:500',
            'collateral_1_owner_name'    => 'required|string|max:150',
            'collateral_1_ownership_status' => 'nullable|string|max:50',
            'collateral_1_doc_type'      => 'nullable|string|max:100',
            'collateral_1_doc_number'    => 'required|string|max:100',
            'collateral_1_client_value'  => 'required|numeric|min:1',
            'collateral_1_doc_photo'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            // Collateral 2
            'collateral_2_type'          => 'nullable|string|max:100',
            'collateral_2_description'   => 'nullable|string|max:500',
            'collateral_2_owner_name'    => 'nullable|string|max:150',
            'collateral_2_ownership_status' => 'nullable|string|max:50',
            'collateral_2_doc_type'      => 'nullable|string|max:100',
            'collateral_2_doc_number'    => 'nullable|string|max:100',
            'collateral_2_client_value'  => 'nullable|numeric|min:0',
            'collateral_2_doc_photo'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            // Declarations
            'consent_verification'       => 'required|accepted',
            'consent_crb'                => 'required|accepted',
            'declaration_truth'          => 'required|accepted',
            // Guarantor 1
            'guarantor_1_name'           => 'required|string|max:150',
            'guarantor_1_relationship'   => 'required|string|max:100',
            'guarantor_1_phone'          => 'required|string|max:20',
            'guarantor_1_commitment_level' => 'required|in:High,Moderate,Low',
            'guarantor_1_pledge_description' => 'nullable|string|max:500',
            'guarantor_1_pledged_asset_value' => 'nullable|numeric|min:0',
            'guarantor_1_signed_consent' => 'nullable|boolean',
            // Guarantor 2
            'guarantor_2_name'           => 'nullable|string|max:150',
            'guarantor_2_relationship'   => 'nullable|string|max:100',
            'guarantor_2_phone'          => 'nullable|string|max:20',
            'guarantor_2_commitment_level' => 'nullable|in:High,Moderate,Low',
            'guarantor_2_pledge_description' => 'nullable|string|max:500',
            'guarantor_2_pledged_asset_value' => 'nullable|numeric|min:0',
            'guarantor_2_signed_consent' => 'nullable|boolean',
        ]);

        // ── Duplicate / pending-loan guard ────────────────────────────────────
        $phone = $validated['phone'];
        $nin   = $validated['national_id'] ?? null;
        $email = $validated['email'] ?? null;

        // 1) Active ClientLoanApplication from same person?
        $openApp = ClientLoanApplication::where(function ($q) use ($phone, $nin, $email) {
                $q->where('phone', $phone);
                if ($nin)   { $q->orWhere('national_id', $nin); }
                if ($email) { $q->orWhere('email', $email); }
            })
            ->whereNotIn('status', ['rejected', 'converted'])
            ->orderByDesc('created_at')
            ->first();

        if ($openApp) {
            $label = match ($openApp->status) {
                'pending_scoring'         => 'awaiting scoring',
                'pending_fo_review'       => 'pending Field Officer review',
                'pending_fo_verification' => 'pending Field Officer verification',
                default                   => $openApp->status,
            };
            return redirect()->back()->withInput()->with(
                'error',
                "You already have an active application ({$openApp->application_code}) that is currently {$label}. ".
                "Please wait for our team to contact you. If you need help, visit your nearest branch."
            );
        }

        // 2) Existing member with a live (non-closed / non-rejected) loan?
        $member = Member::where('contact', $phone)->first();
        if (!$member && $nin)   { $member = Member::where('nin',   $nin)->first(); }
        if (!$member && $email) { $member = Member::where('email', $email)->first(); }

        if ($member) {
            $activeLoan = PersonalLoan::where('member_id', $member->id)
                ->whereNotIn('status', [3, 4])  // 3=closed, 4=rejected
                ->orderByDesc('created_at')
                ->first();

            if ($activeLoan) {
                return redirect()->back()->withInput()->with(
                    'error',
                    "Our records show you already have an active or pending loan with us (Loan #{$activeLoan->code}). ".
                    "Please contact your branch or wait until your current loan is fully settled before applying again."
                );
            }
        }
        // ── End guard ─────────────────────────────────────────────────────────

        try {
            DB::beginTransaction();

            // Cast boolean checkbox fields
            $validated['consent_verification'] = true;
            $validated['consent_crb']           = true;
            $validated['declaration_truth']      = true;
            $validated['has_local_reference']    = (bool) ($request->has_local_reference ?? false);
            $validated['has_external_loans']     = (bool) ($request->has_external_loans ?? false);
            $validated['guarantor_1_signed_consent'] = (bool) ($request->guarantor_1_signed_consent ?? false);
            $validated['guarantor_2_signed_consent'] = (bool) ($request->guarantor_2_signed_consent ?? false);

            // Null-out numeric fields that weren't provided
            foreach (['other_income_claimed', 'external_lenders_count', 'external_outstanding',
                      'external_installment_per_period', 'max_external_arrears_days',
                      'collateral_2_client_value', 'guarantor_1_pledged_asset_value',
                      'guarantor_2_pledged_asset_value'] as $field) {
                $validated[$field] = $validated[$field] ?? 0;
            }

            // If business_type is 'Other', use the custom text value
            if (($validated['business_type'] ?? '') === 'Other' && !empty($request->business_type_custom)) {
                $validated['business_type'] = trim($request->business_type_custom);
            }

            // Handle file uploads using FileStorageService (DigitalOcean Spaces in prod, local fallback)
            $docFields = [
                'business_profile_photo', 'business_activity_photos', 'inventory_photos',
                'sales_book_photo', 'purchases_book_photo', 'expense_records_photo',
                'mobile_money_statements', 'collateral_1_doc_photo', 'collateral_2_doc_photo',
            ];

            foreach ($docFields as $field) {
                if ($request->hasFile($field)) {
                    $validated[$field] = FileStorageService::storeFile(
                        $request->file($field),
                        'client-loan-applications'
                    );
                }
            }

            // Generate application code: CW/D/M + LOAN + timestamp + seq
            $validated['application_code'] = $this->generateCode($validated['repayment_frequency']);
            $validated['status'] = 'pending_scoring';

            // Create the application
            $app = ClientLoanApplication::create($validated);

            // Run scoring synchronously
            $scores = $this->scorer->score($app);
            $app->update($scores);

            DB::commit();

            return redirect()->route('client.apply.success', ['code' => $app->application_code]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Client loan application failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->withInput()
                ->with('error', 'We could not process your application. Please try again or contact our office.');
        }
    }

    /**
     * Show the success / confirmation page with the generated code.
     */
    public function success(Request $request)
    {
        $code = $request->query('code');
        $app  = $code ? ClientLoanApplication::where('application_code', $code)->first() : null;

        return view('client.success', compact('app', 'code'));
    }

    /**
     * Public JSON endpoint: check whether a phone number has an active application.
     * Used by the landing page "Resume" section to inform the user before they try again.
     */
    public function checkStatus(Request $request)
    {
        $phone = trim($request->query('phone', ''));
        if (!$phone) {
            return response()->json(['status' => 'none']);
        }

        $app = ClientLoanApplication::where('phone', $phone)
            ->whereNotIn('status', ['rejected', 'converted'])
            ->orderByDesc('created_at')
            ->first();

        if (!$app) {
            return response()->json(['status' => 'none']);
        }

        $labels = [
            'pending_scoring'         => 'Awaiting Scoring',
            'pending_fo_review'       => 'Pending Field Officer Review',
            'pending_fo_verification' => 'Pending Field Officer Verification',
        ];

        return response()->json([
            'status'           => 'pending',
            'application_code' => $app->application_code,
            'status_label'     => $labels[$app->status] ?? ucwords(str_replace('_', ' ', $app->status)),
            'submitted_at'     => $app->created_at->format('d M Y'),
            'traffic_light'    => $app->traffic_light,
        ]);
    }

    // ── Private ──────────────────────────────────────────────────────────────

    private function generateCode(string $frequency): string
    {
        $prefix    = 'C' . strtoupper(substr($frequency, 0, 1)) . 'LOAN';
        $timestamp = now()->format('ymdHi');
        $seq       = ClientLoanApplication::whereDate('created_at', today())->count() + 1;

        return $prefix . $timestamp . sprintf('%03d', $seq);
    }
}
