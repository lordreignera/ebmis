<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'school_name',
        'school_code',
        'registration_number',
        'school_type',
        'school_type_other',
        'ownership',
        'ownership_type_other',
        'contact_person',
        'contact_position',
        'email',
        'phone',
        'alternative_phone',
        'website',
        'admin_password',
        'password_set_at',
        'physical_address',
        'district',
        'district_other',
        'county',
        'county_other',
        'sub_county',
        'parish',
        'parish_other',
        'village',
        'village_other',
        'gps_coordinates',
        'postal_address',
        'postal_code',
        'year_established',
        'date_of_establishment',
        'total_students',
        'total_teachers',
        'total_non_teaching_staff',
        'total_teaching_staff',
        'current_student_enrollment',
        'maximum_student_capacity',
        'facilities_available',
        'medium_of_instruction',
        'annual_fees_primary',
        'annual_fees_secondary',
        'average_tuition_fees_per_term',
        'student_fees_file_path',
        'other_income_sources',
        'income_sources',
        'income_amounts',
        'monthly_operational_cost',
        'banking_with',
        'current_bank_name',
        'license_number',
        'license_expiry_date',
        'tax_identification_number',
        'has_insurance',
        'insurance_provider',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
        'additional_services',
        'documents_submitted',
        'special_needs_facilities',
        'accepts_scholarship_students',
        // Extended Contact Information
        'school_phone_number',
        'school_email_address',
        'administrator_name',
        'administrator_contact_number',
        'administrator_email',
        // Infrastructure & Facilities
        'number_of_classrooms',
        'number_of_dormitories',
        'number_of_toilets',
        'has_electricity',
        'electricity_provider',
        'water_source',
        'has_internet_access',
        'internet_provider',
        'transport_assets',
        'learning_resources_available',
        // Financial Projections & Cash Flow
        'first_month_revenue',
        'last_month_expenditure',
        'expense_breakdown',
        'past_two_terms_shortfall',
        'expected_shortfall_this_term',
        'unpaid_students_list',
        'reserve_funds_status',
        // Financial Performance
        'average_monthly_income',
        'average_monthly_expenses',
        'profit_or_surplus',
        'banking_institutions_used',
        'has_audited_statements',
        'audited_statements_path',
        // Loan Request Details
        'loan_amount_requested',
        'loan_purpose',
        'preferred_repayment_period',
        'proposed_monthly_installment',
        'has_received_loan_before',
        'previous_loan_details',
        // Supporting Documents
        'registration_certificate_path',
        'school_license_path',
        'bank_statements_path',
        'owner_national_id_path',
        'land_title_path',
        'existing_loan_agreements_path',
        // Institutional Financial Standing
        'current_assets_list',
        'current_liabilities_list',
        'debtors_creditors_list',
        'ministry_of_education_standing',
        'license_validity_status',
        'license_copy_path',
        'ownership_details',
        'has_outstanding_loans',
        'outstanding_loans_details',
        'has_assets_as_collateral',
        'collateral_assets_details',
        // Declarations & Consent
        'declaration_name',
        'declaration_signature_path',
        'declaration_date',
        'consent_to_share_information',
        'assessment_completed_at',
        'assessment_complete',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'password_set_at' => 'datetime',
            'license_expiry_date' => 'date',
            'date_of_establishment' => 'date',
            'declaration_date' => 'date',
            'assessment_completed_at' => 'datetime',
            'approved_at' => 'datetime',
            'has_insurance' => 'boolean',
            'has_electricity' => 'boolean',
            'has_internet_access' => 'boolean',
            'has_audited_statements' => 'boolean',
            'has_received_loan_before' => 'boolean',
            'has_outstanding_loans' => 'boolean',
            'has_assets_as_collateral' => 'boolean',
            'consent_to_share_information' => 'boolean',
            'assessment_complete' => 'boolean',
            'accepts_scholarship_students' => 'boolean',
            'documents_submitted' => 'array',
            'expense_breakdown' => 'array',
            'income_sources' => 'array',
            'income_amounts' => 'array',
            'annual_fees_primary' => 'decimal:2',
            'annual_fees_secondary' => 'decimal:2',
            'average_tuition_fees_per_term' => 'decimal:2',
            'monthly_operational_cost' => 'decimal:2',
            'first_month_revenue' => 'decimal:2',
            'last_month_expenditure' => 'decimal:2',
            'past_two_terms_shortfall' => 'decimal:2',
            'expected_shortfall_this_term' => 'decimal:2',
            'average_monthly_income' => 'decimal:2',
            'average_monthly_expenses' => 'decimal:2',
            'profit_or_surplus' => 'decimal:2',
            'loan_amount_requested' => 'decimal:2',
            'proposed_monthly_installment' => 'decimal:2',
        ];
    }

    // ===============================================================
    // RELATIONSHIPS
    // ===============================================================
    
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    // ===============================================================
    // STATUS METHODS
    // ===============================================================
    
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve the school
     */
    public function approve($approvedBy = null, $notes = null): bool
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Reject the school
     */
    public function reject($rejectedBy = null, $reason = null): bool
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return true;
    }
}