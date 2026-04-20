<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('client_loan_applications');
        Schema::create('client_loan_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_code', 30)->unique();

            // ── Applicant Personal Info ──────────────────────────────────────
            $table->string('full_name');
            $table->string('phone', 20);
            $table->string('email', 150)->nullable();
            $table->string('national_id', 30)->nullable();           // NIN
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();

            // ── Loan Request ─────────────────────────────────────────────────
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->decimal('requested_amount', 15, 2);
            $table->integer('tenure_periods');
            $table->enum('repayment_frequency', ['daily', 'weekly', 'monthly']);
            $table->string('loan_purpose', 500)->nullable();
            $table->string('preferred_disbursement_method', 50)->nullable();   // MTN, Airtel, Cash, Bank

            // ── Residence ───────────────────────────────────────────────────
            $table->string('residence_village', 100)->nullable();
            $table->string('residence_parish', 100)->nullable();
            $table->string('residence_subcounty', 100)->nullable();
            $table->string('residence_district', 100)->nullable();
            $table->string('landmark_directions', 500)->nullable();
            $table->integer('years_at_residence')->nullable();

            // ── Residence Identity Declarations (CDL) ────────────────────────
            $table->string('home_door_color', 100)->nullable();       // HOME_DOOR_COLOR_DECL
            $table->string('home_type', 100)->nullable();             // HOME_TYPE_DECL (structure)
            $table->string('next_of_kin_name', 150)->nullable();      // NEXT_OF_KIN_DECL
            $table->string('next_of_kin_phone', 20)->nullable();      // NEXT_OF_KIN_PHONE_DECL

            // ── LC1 & Community References ───────────────────────────────────
            $table->string('lc1_name', 150)->nullable();
            $table->string('lc1_phone', 20)->nullable();
            $table->boolean('has_local_reference')->default(false);
            $table->string('reference_name', 150)->nullable();        // REF1_NAME_DECL
            $table->string('reference_phone', 20)->nullable();        // REF1_CONTACT_DECL
            $table->string('reference_relationship', 100)->nullable();
            $table->string('reference_2_name', 150)->nullable();      // REF2_NAME_DECL
            $table->string('reference_2_contact', 20)->nullable();    // REF2_CONTACT_DECL
            // Clan / Customary Authority
            $table->string('clan_name', 150)->nullable();             // CLAN_NAME
            $table->string('clan_contact', 20)->nullable();           // CLAN_CONTACT
            $table->boolean('clan_letter_available')->default(false); // CLAN_LETTER_DECL

            // ── Business Profile ─────────────────────────────────────────────
            $table->string('business_name', 200)->nullable();
            $table->string('business_type', 100)->nullable();
            $table->string('business_location', 200)->nullable();
            $table->integer('business_years_operation')->nullable();
            $table->text('business_description')->nullable();
            $table->integer('avg_daily_customers')->nullable();
            $table->integer('business_days_open')->nullable();        // BUSINESS_DAYS_OPEN_DECL
            $table->string('peak_trading_hours', 100)->nullable();    // PEAK_HOURS_DECL (e.g. 5:30pm–8:30pm)
            $table->string('top_supplier_name', 200)->nullable();     // TOP_SUPPLIER_DECL

            // ── Document Upload Flags (Yes/No self-declared + file paths) ────
            $table->string('chairman_letter', 500)->nullable(); // LC1/Chairman introduction letter (mandatory at submission)
            $table->string('business_profile_photo', 500)->nullable();
            $table->string('business_activity_photos', 500)->nullable();
            $table->string('inventory_photos', 500)->nullable();
            $table->string('sales_book_photo', 500)->nullable();
            $table->string('purchases_book_photo', 500)->nullable();
            $table->string('expense_records_photo', 500)->nullable();
            $table->string('mobile_money_statements', 500)->nullable();

            // ── Client Financial Claims (CDL — all figures are MONTHLY) ────────
            $table->decimal('daily_sales_claimed', 15, 2)->default(0);       // DMS  — Monthly Sales
            $table->decimal('monthly_cogs_claimed', 15, 2)->default(0);       // DMCOGS — Cost of Goods Sold
            $table->decimal('business_expenses_claimed', 15, 2)->default(0); // DMOE — Monthly Operating Expenses
            $table->decimal('household_expenses_claimed', 15, 2)->default(0);// DMHE — Monthly Household Expenses
            $table->decimal('other_income_claimed', 15, 2)->default(0);      // DOMI — Other Monthly Income
            $table->text('seasonality_note')->nullable();                      // SEASONALITY_NOTE
            $table->boolean('has_external_loans')->default(false);
            $table->integer('external_lenders_count')->default(0);
            $table->decimal('external_outstanding', 15, 2)->default(0);       // ACTIVE_LOAN_OUTSTANDING
            $table->decimal('external_installment_per_period', 15, 2)->default(0); // DMELI
            $table->integer('max_external_arrears_days')->default(0);

            // ── Collateral 1 ─────────────────────────────────────────────────
            $table->string('collateral_1_type', 100)->nullable();
            $table->text('collateral_1_description')->nullable();
            $table->string('collateral_1_owner_name', 150)->nullable();
            $table->string('collateral_1_ownership_status', 50)->nullable();
            $table->string('collateral_1_doc_type', 100)->nullable();
            $table->string('collateral_1_doc_number', 100)->nullable();
            $table->decimal('collateral_1_client_value', 15, 2)->default(0);
            $table->boolean('collateral_1_pledged')->default(false);   // COL1_PLEDGED
            $table->boolean('collateral_1_customary')->default(false); // COL1_CUSTOMARY (ancestral land)
            $table->string('collateral_1_doc_photo', 500)->nullable();

            // ── Collateral 2 (optional) ──────────────────────────────────────
            $table->string('collateral_2_type', 100)->nullable();
            $table->text('collateral_2_description')->nullable();
            $table->string('collateral_2_owner_name', 150)->nullable();
            $table->string('collateral_2_ownership_status', 50)->nullable();
            $table->string('collateral_2_doc_type', 100)->nullable();
            $table->string('collateral_2_doc_number', 100)->nullable();
            $table->decimal('collateral_2_client_value', 15, 2)->default(0);
            $table->boolean('collateral_2_pledged')->default(false);   // COL2_PLEDGED
            $table->boolean('collateral_2_customary')->default(false); // COL2_CUSTOMARY
            $table->string('collateral_2_doc_photo', 500)->nullable();

            // ── Declarations ─────────────────────────────────────────────────
            $table->boolean('consent_verification')->default(false);
            $table->boolean('consent_crb')->default(false);
            $table->boolean('declaration_truth')->default(false);

            // ── Guarantor 1 ──────────────────────────────────────────────────
            $table->string('guarantor_1_name', 150)->nullable();
            $table->string('guarantor_1_relationship', 100)->nullable();
            $table->string('guarantor_1_phone', 20)->nullable();
            $table->enum('guarantor_1_commitment_level', ['High', 'Moderate', 'Low'])->nullable();
            $table->text('guarantor_1_pledge_description')->nullable();
            $table->decimal('guarantor_1_pledged_asset_value', 15, 2)->default(0);
            $table->decimal('guarantor_1_monthly_income', 15, 2)->nullable();  // G1_INCOME_DECL
            $table->string('guarantor_1_support_description', 300)->nullable(); // G1_SUPPORT_DECL
            $table->boolean('guarantor_1_signed_consent')->default(false);

            // ── Guarantor 2 (optional) ───────────────────────────────────────
            $table->string('guarantor_2_name', 150)->nullable();
            $table->string('guarantor_2_relationship', 100)->nullable();
            $table->string('guarantor_2_phone', 20)->nullable();
            $table->enum('guarantor_2_commitment_level', ['High', 'Moderate', 'Low'])->nullable();
            $table->text('guarantor_2_pledge_description')->nullable();
            $table->decimal('guarantor_2_pledged_asset_value', 15, 2)->default(0);
            $table->decimal('guarantor_2_monthly_income', 15, 2)->nullable();  // G2_INCOME_DECL
            $table->string('guarantor_2_support_description', 300)->nullable(); // G2_SUPPORT_DECL
            $table->boolean('guarantor_2_signed_consent')->default(false);

            // ── Auto-Scoring Results ─────────────────────────────────────────
            $table->integer('es_score')->nullable();                  // Evidence Score 0-100
            $table->integer('vss_score')->nullable();                 // Verification Strength Score 0-100
            $table->decimal('daily_disposable_income', 15, 2)->nullable();
            $table->decimal('proposed_installment', 15, 2)->nullable();
            $table->decimal('total_debt_per_period', 15, 2)->nullable();
            $table->decimal('dscr', 8, 2)->nullable();               // Debt Service Coverage Ratio
            $table->decimal('fsv_collateral_1', 15, 2)->nullable();
            $table->decimal('fsv_collateral_2', 15, 2)->nullable();
            $table->decimal('fsv_total', 15, 2)->nullable();
            $table->decimal('collateral_coverage', 8, 2)->nullable();
            $table->integer('collateral_1_saleability_score')->nullable();
            $table->integer('collateral_2_saleability_score')->nullable();
            $table->integer('collateral_saleability_score')->nullable();
            $table->decimal('guarantor_1_security', 15, 2)->nullable();
            $table->decimal('guarantor_2_security', 15, 2)->nullable();
            $table->decimal('guarantor_security_total', 15, 2)->nullable();
            $table->integer('guarantor_strength_score')->nullable();
            $table->integer('composite_score')->nullable();
            $table->enum('risk_band', ['Low', 'Medium', 'High'])->nullable();
            $table->enum('traffic_light', ['GREEN', 'YELLOW', 'RED'])->nullable();
            $table->json('gate_status')->nullable();
            $table->text('system_notes')->nullable();
            $table->decimal('max_approvable_amount', 15, 2)->nullable();

            // ── Workflow Status ───────────────────────────────────────────────
            $table->enum('status', [
                'pending_fo_verification',// STEP 1: Submitted, awaiting FO field visit & FVL
                'pending_scoring',        // STEP 2: FVL submitted, scoring not yet run
                'pending_fo_review',      // STEP 3: Scored, awaiting FO decision (GREEN/YELLOW)
                'approved',               // FO approved, Member+Loan records created
                'rejected',               // Declined (auto or FO)
                'converted',              // Successfully converted to active loan
            ])->default('pending_fo_verification');

            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();

            // ── Post-Conversion Links ─────────────────────────────────────────
            $table->unsignedBigInteger('member_id')->nullable()->index();
            $table->unsignedBigInteger('loan_id')->nullable()->index();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('phone');
            $table->index('national_id');
        });

        // Add foreign keys separately so a type mismatch on the referenced table
        // does not prevent the main table from being created.
        $fks = [
            ['branch_id',   'branches',       'id'],
            ['product_id',  'products',       'id'],
            ['reviewed_by', 'users',          'id'],
            ['member_id',   'members',        'id'],
            ['loan_id',     'personal_loans', 'id'],
        ];

        foreach ($fks as [$col, $refTable, $refCol]) {
            try {
                Schema::table('client_loan_applications', function (Blueprint $table) use ($col, $refTable, $refCol) {
                    $table->foreign($col)->references($refCol)->on($refTable)->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // FK could not be added (type mismatch on production). The app
                // enforces referential integrity at the application layer.
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_loan_applications');
    }
};
