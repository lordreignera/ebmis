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
        Schema::table('schools', function (Blueprint $table) {
            // Section 1: Additional School Identification
            $table->date('date_of_establishment')->nullable()->after('year_established');
            $table->string('school_type_other')->nullable()->after('school_type');
            $table->string('ownership_type_other')->nullable()->after('ownership');
            
            // Section 4: Extended Location Details
            $table->string('gps_coordinates')->nullable()->after('village');
            
            // Section 5: Extended Contact Information
            $table->string('school_phone_number')->nullable()->after('phone');
            $table->string('school_email_address')->nullable()->after('email');
            $table->string('administrator_name')->nullable()->after('contact_person');
            $table->string('administrator_contact_number')->nullable()->after('administrator_name');
            $table->string('administrator_email')->nullable()->after('administrator_contact_number');
            
            // Section 6: Staffing & Enrollment
            $table->integer('total_teaching_staff')->nullable()->after('total_teachers');
            $table->integer('current_student_enrollment')->nullable()->after('total_students');
            $table->integer('maximum_student_capacity')->nullable()->after('current_student_enrollment');
            $table->decimal('average_tuition_fees_per_term', 15, 2)->nullable()->after('annual_fees_secondary');
            $table->text('other_income_sources')->nullable()->comment('boarding, uniforms, canteen, etc.');
            
            // Section 7: Infrastructure & Facilities
            $table->integer('number_of_classrooms')->nullable();
            $table->integer('number_of_dormitories')->nullable();
            $table->integer('number_of_toilets')->nullable();
            $table->boolean('has_electricity')->default(false);
            $table->string('electricity_provider')->nullable();
            $table->string('water_source')->nullable()->comment('Piped/Borehole/Rainwater/Other');
            $table->boolean('has_internet_access')->default(false);
            $table->string('internet_provider')->nullable();
            $table->text('transport_assets')->nullable()->comment('School Bus/Van/Motorcycle/Other');
            $table->text('learning_resources_available')->nullable()->comment('Library/Lab/Computers/Textbooks/Other');
            
            // Section 8: Financial Projections & Cash Flow
            $table->decimal('first_month_revenue', 15, 2)->nullable()->comment('Total revenue in 1st month of term');
            $table->decimal('last_month_expenditure', 15, 2)->nullable()->comment('Last month total operating expenditure');
            $table->json('expense_breakdown')->nullable()->comment('Salaries, Utilities, Maintenance, etc.');
            $table->decimal('past_two_terms_shortfall', 15, 2)->nullable();
            $table->decimal('expected_shortfall_this_term', 15, 2)->nullable();
            $table->text('unpaid_students_list')->nullable()->comment('List of students who did not pay full tuition');
            $table->string('reserve_funds_status')->nullable()->comment('Sufficient for one term/Limited/No reserves');
            
            // Section 9: Financial Performance
            $table->decimal('average_monthly_income', 15, 2)->nullable();
            $table->decimal('average_monthly_expenses', 15, 2)->nullable();
            $table->decimal('profit_or_surplus', 15, 2)->nullable();
            $table->string('banking_institutions_used')->nullable();
            $table->boolean('has_audited_statements')->default(false);
            $table->string('audited_statements_path')->nullable();
            
            // Section 10: Loan Request Details
            $table->decimal('loan_amount_requested', 15, 2)->nullable();
            $table->text('loan_purpose')->nullable()->comment('Infrastructure/Salaries/Equipment/Expansion/Other');
            $table->string('preferred_repayment_period')->nullable()->comment('6/12/24 months or other');
            $table->decimal('proposed_monthly_installment', 15, 2)->nullable();
            $table->boolean('has_received_loan_before')->default(false);
            $table->text('previous_loan_details')->nullable()->comment('Institution and amount');
            
            // Section 11: Supporting Documents (paths stored as JSON)
            $table->string('registration_certificate_path')->nullable();
            $table->string('school_license_path')->nullable();
            $table->string('bank_statements_path')->nullable();
            $table->string('owner_national_id_path')->nullable();
            $table->string('land_title_path')->nullable();
            $table->string('existing_loan_agreements_path')->nullable();
            
            // Section 12: Institutional Financial Standing & Ownership
            $table->text('current_assets_list')->nullable();
            $table->text('current_liabilities_list')->nullable();
            $table->text('debtors_creditors_list')->nullable();
            $table->string('ministry_of_education_standing')->nullable()->comment('Fully registered/Provisionally/Pending/Not registered');
            $table->string('license_validity_status')->nullable()->comment('Valid and current/Expired/No license');
            $table->string('license_copy_path')->nullable();
            $table->text('ownership_details')->nullable()->comment('Owner names and percentage shares');
            $table->boolean('has_outstanding_loans')->default(false);
            $table->text('outstanding_loans_details')->nullable()->comment('Institution, amount, maturity date');
            $table->boolean('has_assets_as_collateral')->default(false);
            $table->text('collateral_assets_details')->nullable()->comment('Assets and institutions');
            
            // Section 13: Declarations & Consent
            $table->string('declaration_name')->nullable();
            $table->string('declaration_signature_path')->nullable();
            $table->date('declaration_date')->nullable();
            $table->boolean('consent_to_share_information')->default(false);
            
            // Additional tracking
            $table->timestamp('assessment_completed_at')->nullable();
            $table->boolean('assessment_complete')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_establishment',
                'school_type_other',
                'ownership_type_other',
                'gps_coordinates',
                'school_phone_number',
                'school_email_address',
                'administrator_name',
                'administrator_contact_number',
                'administrator_email',
                'total_teaching_staff',
                'current_student_enrollment',
                'maximum_student_capacity',
                'average_tuition_fees_per_term',
                'other_income_sources',
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
                'first_month_revenue',
                'last_month_expenditure',
                'expense_breakdown',
                'past_two_terms_shortfall',
                'expected_shortfall_this_term',
                'unpaid_students_list',
                'reserve_funds_status',
                'average_monthly_income',
                'average_monthly_expenses',
                'profit_or_surplus',
                'banking_institutions_used',
                'has_audited_statements',
                'audited_statements_path',
                'loan_amount_requested',
                'loan_purpose',
                'preferred_repayment_period',
                'proposed_monthly_installment',
                'has_received_loan_before',
                'previous_loan_details',
                'registration_certificate_path',
                'school_license_path',
                'bank_statements_path',
                'owner_national_id_path',
                'land_title_path',
                'existing_loan_agreements_path',
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
                'declaration_name',
                'declaration_signature_path',
                'declaration_date',
                'consent_to_share_information',
                'assessment_completed_at',
                'assessment_complete',
            ]);
        });
    }
};
