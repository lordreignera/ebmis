<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientLoanApplication extends Model
{
    protected $table = 'client_loan_applications';

    protected $fillable = [
        'application_code',
        // Applicant
        'full_name', 'phone', 'email', 'national_id', 'date_of_birth', 'gender', 'branch_id',
        // Loan request
        'product_id', 'requested_amount', 'tenure_periods', 'repayment_frequency',
        'loan_purpose', 'preferred_disbursement_method',
        // Residence
        'residence_village', 'residence_parish', 'residence_subcounty', 'residence_district',
        'landmark_directions', 'years_at_residence',
        // LC1 & Reference
        'lc1_name', 'lc1_phone', 'has_local_reference',
        'reference_name', 'reference_phone', 'reference_relationship',
        // Business
        'business_name', 'business_type', 'business_location',
        'business_years_operation', 'business_description', 'avg_daily_customers',
        // Documents
        'business_profile_photo', 'business_activity_photos', 'inventory_photos',
        'sales_book_photo', 'purchases_book_photo', 'expense_records_photo', 'mobile_money_statements',
        // Financial claims
        'daily_sales_claimed', 'business_expenses_claimed', 'household_expenses_claimed',
        'other_income_claimed', 'has_external_loans', 'external_lenders_count',
        'external_outstanding', 'external_installment_per_period', 'max_external_arrears_days',
        // Collateral 1
        'collateral_1_type', 'collateral_1_description', 'collateral_1_owner_name',
        'collateral_1_ownership_status', 'collateral_1_doc_type', 'collateral_1_doc_number',
        'collateral_1_client_value', 'collateral_1_doc_photo',
        // Collateral 2
        'collateral_2_type', 'collateral_2_description', 'collateral_2_owner_name',
        'collateral_2_ownership_status', 'collateral_2_doc_type', 'collateral_2_doc_number',
        'collateral_2_client_value', 'collateral_2_doc_photo',
        // Declarations
        'consent_verification', 'consent_crb', 'declaration_truth',
        // Guarantor 1
        'guarantor_1_name', 'guarantor_1_relationship', 'guarantor_1_phone',
        'guarantor_1_commitment_level', 'guarantor_1_pledge_description',
        'guarantor_1_pledged_asset_value', 'guarantor_1_signed_consent',
        // Guarantor 2
        'guarantor_2_name', 'guarantor_2_relationship', 'guarantor_2_phone',
        'guarantor_2_commitment_level', 'guarantor_2_pledge_description',
        'guarantor_2_pledged_asset_value', 'guarantor_2_signed_consent',
        // Scoring
        'es_score', 'vss_score', 'daily_disposable_income', 'proposed_installment',
        'total_debt_per_period', 'dscr', 'fsv_collateral_1', 'fsv_collateral_2', 'fsv_total',
        'collateral_coverage', 'collateral_1_saleability_score', 'collateral_2_saleability_score',
        'collateral_saleability_score', 'guarantor_1_security', 'guarantor_2_security',
        'guarantor_security_total', 'guarantor_strength_score',
        'composite_score', 'risk_band', 'traffic_light', 'gate_status', 'system_notes',
        'max_approvable_amount',
        // Workflow
        'status', 'rejection_reason', 'reviewed_by', 'reviewed_at',
        'member_id', 'loan_id',
    ];

    protected $casts = [
        'date_of_birth'           => 'date',
        'has_local_reference'     => 'boolean',
        'has_external_loans'      => 'boolean',
        'consent_verification'    => 'boolean',
        'consent_crb'             => 'boolean',
        'declaration_truth'       => 'boolean',
        'guarantor_1_signed_consent' => 'boolean',
        'guarantor_2_signed_consent' => 'boolean',
        'gate_status'             => 'array',
        'reviewed_at'             => 'datetime',
        'requested_amount'        => 'decimal:2',
        'daily_sales_claimed'     => 'decimal:2',
        'business_expenses_claimed' => 'decimal:2',
        'household_expenses_claimed' => 'decimal:2',
        'other_income_claimed'    => 'decimal:2',
        'external_outstanding'    => 'decimal:2',
        'external_installment_per_period' => 'decimal:2',
        'collateral_1_client_value' => 'decimal:2',
        'collateral_2_client_value' => 'decimal:2',
        'guarantor_1_pledged_asset_value' => 'decimal:2',
        'guarantor_2_pledged_asset_value' => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(PersonalLoan::class, 'loan_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function trafficLightClass(): string
    {
        return match ($this->traffic_light) {
            'GREEN'  => 'success',
            'YELLOW' => 'warning',
            'RED'    => 'danger',
            default  => 'secondary',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending_scoring'         => 'secondary',
            'pending_fo_review'       => 'info',
            'pending_fo_verification' => 'warning',
            'approved'                => 'success',
            'rejected'                => 'danger',
            'converted'               => 'primary',
            default                   => 'secondary',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending_scoring'         => 'Pending Scoring',
            'pending_fo_review'       => 'Pending FO Review',
            'pending_fo_verification' => 'Pending FO Verification',
            'approved'                => 'Approved',
            'rejected'                => 'Rejected',
            'converted'               => 'Converted to Loan',
            default                   => 'Unknown',
        };
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePendingScoring($query)
    {
        return $query->where('status', 'pending_scoring');
    }

    public function scopePendingFoReview($query)
    {
        return $query->whereIn('status', ['pending_fo_review', 'pending_fo_verification']);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['rejected', 'converted']);
    }
}
