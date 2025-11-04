<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            
            // ============================================
            // SECTION 1: Basic School Information
            // ============================================
            $table->string('school_name', 255);
            $table->string('school_code', 50)->unique()->nullable();
            $table->string('registration_number', 100)->nullable();
            $table->enum('school_type', ['Primary', 'Secondary', 'Primary & Secondary', 'Nursery', 'University', 'College', 'Other'])->default('Primary');
            $table->string('school_type_other')->nullable();
            $table->json('school_types')->nullable()->comment('Multiple school types from checkboxes');
            $table->enum('ownership', ['Government', 'Private', 'Religious', 'Community', 'NGO'])->default('Private');
            $table->string('ownership_type_other')->nullable();
            $table->year('year_established')->nullable();
            $table->date('date_of_establishment')->nullable();
            
            // ============================================
            // SECTION 2: Contact Information
            // ============================================
            $table->string('contact_person', 255);
            $table->string('contact_position', 100)->nullable();
            $table->string('email', 191)->unique();
            $table->string('phone', 50);
            $table->string('alternative_phone', 50)->nullable();
            $table->string('website', 255)->nullable();
            
            // Extended Contact Information
            $table->string('school_phone_number')->nullable();
            $table->string('school_email_address')->nullable();
            $table->string('administrator_name')->nullable();
            $table->string('administrator_contact_number')->nullable();
            $table->string('administrator_email')->nullable();
            
            // Admin Account Credentials
            $table->string('admin_password');
            $table->timestamp('password_set_at')->nullable();
            
            // ============================================
            // SECTION 3: Location Information
            // ============================================
            $table->text('physical_address');
            $table->string('district', 100);
            $table->string('district_other')->nullable();
            $table->string('county', 100)->nullable();
            $table->string('county_other')->nullable();
            $table->string('sub_county', 100)->nullable();
            $table->string('parish', 100)->nullable();
            $table->string('parish_other')->nullable();
            $table->string('village', 100)->nullable();
            $table->string('village_other')->nullable();
            $table->string('gps_coordinates')->nullable();
            $table->string('postal_address', 255)->nullable();
            $table->string('postal_code', 20)->nullable();
            
            // ============================================
            // SECTION 4: Staffing & Enrollment
            // ============================================
            $table->integer('total_students')->default(0);
            $table->integer('total_teachers')->default(0);
            $table->integer('total_non_teaching_staff')->default(0);
            $table->integer('total_teaching_staff')->nullable();
            $table->integer('current_student_enrollment')->nullable();
            $table->integer('maximum_student_capacity')->nullable();
            
            // ============================================
            // SECTION 5: Facilities & Infrastructure
            // ============================================
            $table->text('facilities_available')->nullable();
            $table->enum('medium_of_instruction', ['English', 'Local Language', 'Both'])->default('English');
            $table->integer('number_of_classrooms')->nullable();
            $table->integer('number_of_dormitories')->nullable();
            $table->integer('number_of_toilets')->nullable();
            $table->boolean('has_electricity')->default(false);
            $table->string('electricity_provider')->nullable();
            $table->string('electricity_provider_other', 255)->nullable();
            $table->string('water_source')->nullable()->comment('Piped/Borehole/Rainwater/Other');
            $table->boolean('has_internet_access')->default(false);
            $table->string('internet_provider')->nullable();
            $table->string('internet_provider_other', 255)->nullable();
            $table->json('transport_assets')->nullable()->comment('School Bus/Van/Motorcycle/Other');
            $table->string('transport_assets_other', 255)->nullable();
            $table->json('learning_resources_available')->nullable()->comment('Library/Lab/Computers/Textbooks/Other');
            $table->string('learning_resources_other', 255)->nullable();
            $table->text('special_needs_facilities')->nullable();
            
            // ============================================
            // SECTION 6: Financial Information - Fees & Income
            // ============================================
            $table->decimal('annual_fees_primary', 10, 2)->nullable();
            $table->decimal('annual_fees_secondary', 10, 2)->nullable();
            $table->decimal('average_tuition_fees_per_term', 15, 2)->nullable();
            $table->string('student_fees_file_path')->nullable()->comment('Path to uploaded student fees Excel file');
            $table->text('other_income_sources')->nullable()->comment('boarding, uniforms, canteen, etc.');
            $table->json('income_sources')->nullable()->comment('Array of income source names');
            $table->json('income_amounts')->nullable()->comment('Array of income amounts corresponding to sources');
            
            // ============================================
            // SECTION 7: Financial Projections & Cash Flow
            // ============================================
            $table->decimal('first_month_revenue', 15, 2)->nullable()->comment('Total revenue in 1st month of term');
            $table->decimal('last_month_expenditure', 15, 2)->nullable()->comment('Last month total operating expenditure');
            $table->decimal('monthly_operational_cost', 15, 2)->nullable();
            $table->json('expense_breakdown')->nullable()->comment('Salaries, Utilities, Maintenance, etc.');
            $table->json('expense_categories')->nullable();
            $table->json('expense_amounts')->nullable();
            $table->decimal('past_two_terms_shortfall', 15, 2)->nullable();
            $table->decimal('expected_shortfall_this_term', 15, 2)->nullable();
            $table->text('unpaid_students_list')->nullable()->comment('List of students who did not pay full tuition');
            $table->string('unpaid_students_file_path', 500)->nullable();
            $table->string('reserve_funds_status')->nullable()->comment('Sufficient for one term/Limited/No reserves');
            
            // ============================================
            // SECTION 8: Financial Performance
            // ============================================
            $table->decimal('average_monthly_income', 15, 2)->nullable();
            $table->decimal('average_monthly_expenses', 15, 2)->nullable();
            $table->decimal('profit_or_surplus', 15, 2)->nullable();
            $table->enum('banking_with', ['Bank', 'SACCO', 'Microfinance', 'None', 'Other'])->nullable();
            $table->string('current_bank_name', 255)->nullable();
            $table->string('banking_institutions_used')->nullable();
            $table->string('banking_institutions_other', 255)->nullable();
            $table->boolean('has_audited_statements')->default(false);
            $table->string('audited_statements_path')->nullable();
            
            // ============================================
            // SECTION 9: Loan Request Details
            // ============================================
            $table->decimal('loan_amount_requested', 15, 2)->nullable();
            $table->text('loan_purpose')->nullable()->comment('Infrastructure/Salaries/Equipment/Expansion/Other');
            $table->string('preferred_repayment_period')->nullable()->comment('6/12/24 months or other');
            $table->decimal('proposed_monthly_installment', 15, 2)->nullable();
            $table->boolean('has_received_loan_before')->default(false);
            $table->text('previous_loan_details')->nullable()->comment('Institution and amount');
            
            // ============================================
            // SECTION 10: Supporting Documents
            // ============================================
            $table->json('documents_submitted')->nullable();
            $table->string('registration_certificate_path')->nullable();
            $table->string('school_license_path')->nullable();
            $table->string('bank_statements_path')->nullable();
            $table->string('owner_national_id_path')->nullable();
            $table->string('land_title_path')->nullable();
            $table->string('existing_loan_agreements_path')->nullable();
            
            // ============================================
            // SECTION 11: Institutional Standing & Ownership
            // ============================================
            $table->text('current_assets_list')->nullable();
            $table->text('current_liabilities_list')->nullable();
            $table->text('debtors_creditors_list')->nullable();
            $table->string('ministry_of_education_standing')->nullable()->comment('Fully registered/Provisionally/Pending/Not registered');
            $table->string('license_validity_status')->nullable()->comment('Valid and current/Expired/No license');
            $table->string('license_number', 100)->nullable();
            $table->date('license_expiry_date')->nullable();
            $table->string('license_copy_path')->nullable();
            
            // Ownership Details - JSON format for dynamic list
            $table->json('ownership_details')->nullable()->comment('Array of owner names and percentage shares');
            
            $table->boolean('has_outstanding_loans')->default(false);
            $table->text('outstanding_loans_details')->nullable()->comment('Institution, amount, maturity date');
            $table->boolean('has_assets_as_collateral')->default(false);
            $table->text('collateral_assets_details')->nullable()->comment('Assets and institutions');
            
            // ============================================
            // SECTION 12: Legal & Compliance
            // ============================================
            $table->string('tax_identification_number', 100)->nullable();
            $table->boolean('has_insurance')->default(false);
            $table->string('insurance_provider', 255)->nullable();
            
            // ============================================
            // SECTION 13: Declarations & Consent
            // ============================================
            $table->string('declaration_name')->nullable();
            $table->string('declaration_signature_path')->nullable();
            $table->date('declaration_date')->nullable();
            $table->boolean('consent_to_share_information')->default(false);
            
            // ============================================
            // SECTION 14: System Status & Tracking
            // ============================================
            $table->enum('status', ['pending', 'approved', 'suspended', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Assessment Completion Tracking
            $table->timestamp('assessment_completed_at')->nullable();
            $table->boolean('assessment_complete')->default(false);
            $table->decimal('assessment_completion_percentage', 5, 2)->default(0);
            
            // Additional
            $table->text('additional_services')->nullable();
            $table->boolean('accepts_scholarship_students')->default(false);
            
            $table->timestamps();
            
            // ============================================
            // Indexes for Performance
            // ============================================
            $table->index(['status', 'district']);
            $table->index(['school_type', 'ownership']);
            $table->index('assessment_complete');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
