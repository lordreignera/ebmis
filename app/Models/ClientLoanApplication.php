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
        // Residence identity (CDL)
        'home_door_color', 'home_type', 'next_of_kin_name', 'next_of_kin_phone',
        // LC1 & Community references
        'lc1_name', 'lc1_phone', 'has_local_reference',
        'reference_name', 'reference_phone', 'reference_relationship',
        'reference_2_name', 'reference_2_contact',
        'clan_name', 'clan_contact', 'clan_letter_available',
        // Business
        'business_name', 'business_type', 'business_location',
        'business_years_operation', 'business_description', 'avg_daily_customers',
        'business_days_open', 'peak_trading_hours', 'top_supplier_name',
        // Documents
        'chairman_letter',
        'business_profile_photo', 'business_activity_photos', 'inventory_photos',
        'sales_book_photo', 'purchases_book_photo', 'expense_records_photo', 'mobile_money_statements',
        // Financial claims (all monthly figures per CDL)
        'daily_sales_claimed',           // DMS  — Monthly Sales (field name kept for compat)
        'monthly_cogs_claimed',          // DMCOGS — Monthly Cost of Goods Sold
        'business_expenses_claimed',     // DMOE — Monthly Operating Expenses
        'household_expenses_claimed',    // DMHE — Monthly Household Expenses
        'other_income_claimed',          // DOMI — Other Monthly Income
        'seasonality_note',
        'has_external_loans', 'external_lenders_count',
        'external_outstanding', 'external_installment_per_period', 'max_external_arrears_days',
        // Collateral 1
        'collateral_1_type', 'collateral_1_description', 'collateral_1_owner_name',
        'collateral_1_ownership_status', 'collateral_1_doc_type', 'collateral_1_doc_number',
        'collateral_1_client_value', 'collateral_1_pledged', 'collateral_1_customary',
        'collateral_1_doc_photo',
        // Collateral 2
        'collateral_2_type', 'collateral_2_description', 'collateral_2_owner_name',
        'collateral_2_ownership_status', 'collateral_2_doc_type', 'collateral_2_doc_number',
        'collateral_2_client_value', 'collateral_2_pledged', 'collateral_2_customary',
        'collateral_2_doc_photo',
        // Declarations
        'consent_verification', 'consent_crb', 'declaration_truth',
        // Guarantor 1
        'guarantor_1_name', 'guarantor_1_relationship', 'guarantor_1_phone',
        'guarantor_1_commitment_level', 'guarantor_1_pledge_description',
        'guarantor_1_pledged_asset_value', 'guarantor_1_monthly_income', 'guarantor_1_support_description',
        'guarantor_1_signed_consent',
        // Guarantor 2
        'guarantor_2_name', 'guarantor_2_relationship', 'guarantor_2_phone',
        'guarantor_2_commitment_level', 'guarantor_2_pledge_description',
        'guarantor_2_pledged_asset_value', 'guarantor_2_monthly_income', 'guarantor_2_support_description',
        'guarantor_2_signed_consent',
        // SDL component scores
        'kyc_score', 'ivi_score', 'cf_score', 'crb_score', 'col_score',
        'ss_score', 'exp_score', 'rpc_score', 'crd_score',
        'sdl_score', 'fraud_flag_count',
        // Legacy / backward-compat score columns
        'es_score', 'vss_score', 'daily_disposable_income', 'proposed_installment',
        'total_debt_per_period', 'dscr', 'stressed_dscr',
        'fsv_collateral_1', 'fsv_collateral_2', 'fsv_total',
        'collateral_coverage', 'collateral_1_saleability_score', 'collateral_2_saleability_score',
        'collateral_saleability_score', 'guarantor_1_security', 'guarantor_2_security',
        'guarantor_security_total', 'guarantor_strength_score',
        'composite_score', 'risk_band', 'traffic_light', 'gate_status', 'system_notes',
        // Amount logic
        'mai', 'maa', 'mcl', 'faa', 'approved_amount', 'max_approvable_amount',
        // Decision
        'final_decision',
        // Workflow
        'status', 'rejection_reason', 'reviewed_by', 'reviewed_at',
        'member_id', 'loan_id',
    ];

    protected $casts = [
        'date_of_birth'               => 'date',
        'has_local_reference'         => 'boolean',
        'clan_letter_available'       => 'boolean',
        'collateral_1_pledged'        => 'boolean',
        'collateral_1_customary'      => 'boolean',
        'collateral_2_pledged'        => 'boolean',
        'collateral_2_customary'      => 'boolean',
        'has_external_loans'          => 'boolean',
        'consent_verification'        => 'boolean',
        'consent_crb'                 => 'boolean',
        'declaration_truth'           => 'boolean',
        'guarantor_1_signed_consent'  => 'boolean',
        'guarantor_2_signed_consent'  => 'boolean',
        'gate_status'                 => 'array',
        'reviewed_at'                 => 'datetime',
        'requested_amount'            => 'decimal:2',
        'daily_sales_claimed'         => 'decimal:2',
        'monthly_cogs_claimed'        => 'decimal:2',
        'business_expenses_claimed'   => 'decimal:2',
        'household_expenses_claimed'  => 'decimal:2',
        'other_income_claimed'        => 'decimal:2',
        'external_outstanding'        => 'decimal:2',
        'external_installment_per_period' => 'decimal:2',
        'collateral_1_client_value'   => 'decimal:2',
        'collateral_2_client_value'   => 'decimal:2',
        'guarantor_1_pledged_asset_value' => 'decimal:2',
        'guarantor_2_pledged_asset_value' => 'decimal:2',
        'guarantor_1_monthly_income'  => 'decimal:2',
        'guarantor_2_monthly_income'  => 'decimal:2',
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

    public function fieldVerification(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FieldVerification::class, 'application_id');
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
            'pending_fo_verification' => 'warning',
            'pending_scoring'         => 'secondary',
            'pending_fo_review'       => 'info',
            'approved'                => 'success',
            'rejected'                => 'danger',
            'converted'               => 'primary',
            default                   => 'secondary',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending_fo_verification' => 'Pending FO Field Visit',
            'pending_scoring'         => 'Pending Scoring',
            'pending_fo_review'       => 'Pending FO Review',
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
