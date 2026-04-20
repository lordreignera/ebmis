<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Field Verification Layer (FVL) — completed by the Field Officer
 * after a physical client visit, BEFORE the system scores the application.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_loan_field_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id')->unique()->index();
            $table->unsignedBigInteger('verified_by')->nullable()->index();  // User (FO) ID
            $table->timestamp('visit_start')->nullable();
            $table->timestamp('visit_end')->nullable();

            // ── KYC / Know Your Customer ──────────────────────────────────────
            $table->boolean('idv')->default(false);           // Identity verified
            $table->string('idv_status', 30)->nullable();     // Verified / Partially Verified / N/A
            $table->boolean('pvs')->default(false);           // Phone verified & reached
            $table->string('pvs_status', 30)->nullable();
            $table->boolean('avs')->default(false);           // Address + business visited
            $table->string('avs_status', 30)->nullable();
            $table->integer('years_residence_v')->nullable(); // Confirmed years at residence
            $table->string('res_landmark_seen', 300)->nullable();   // FO observed landmark text (compare with CDL landmark_directions)
            $table->string('home_door_color_seen', 100)->nullable(); // FO observed door color text (compare with CDL home_door_color)
            $table->boolean('next_of_kin_v')->default(false);
            $table->string('next_of_kin_status', 30)->nullable();

            // ── In-Person Visit Integrity Controls ───────────────────────────
            $table->string('gps_capture', 100)->nullable();           // lat,lng
            $table->string('device_id', 100)->nullable();
            $table->string('client_home_photo', 500)->nullable();
            $table->string('client_business_photo', 500)->nullable();
            $table->string('customer_unposed_photo', 500)->nullable();
            $table->string('officer_selfie_client', 500)->nullable();
            $table->string('live_business_stock_photo', 500)->nullable();
            $table->text('on_site_question')->nullable();
            $table->text('on_site_answer')->nullable();

            // ── Business & Cash Flow Verification ────────────────────────────
            $table->decimal('v_monthly_sales', 15, 2)->nullable();    // VDMS
            $table->decimal('v_other_income', 15, 2)->nullable();     // VOMI
            $table->decimal('v_cogs', 15, 2)->nullable();             // VMCOGS
            $table->decimal('v_opex', 15, 2)->nullable();             // VMOE
            $table->decimal('v_household_expenses', 15, 2)->nullable(); // VMHE
            $table->decimal('v_loan_installment', 15, 2)->nullable();   // VMELI
            $table->boolean('sales_record_seen')->default(false);
            $table->boolean('mobile_money_seen')->default(false);
            $table->boolean('supplier_confirmed')->default(false);
            $table->string('supplier_confirmed_name', 200)->nullable();
            $table->boolean('business_open_v')->default(false);     // Business open & operating during visit
            $table->integer('business_open_days_v')->nullable();      // Verified days/week
            $table->string('peak_hours_v', 100)->nullable();          // Verified peak hours
            $table->integer('avg_customers_v')->nullable();           // Verified avg customers

            // ── CRB / Credit Reference Check ─────────────────────────────────
            $table->integer('crb_defaults')->nullable();
            $table->integer('crb_arrears')->nullable();
            $table->integer('crb_nxt_count')->nullable();             // External loan count
            $table->decimal('crb_ext_inst', 15, 2)->nullable();      // External installment
            $table->boolean('crb_skip_flag')->default(false);        // Negative score flag

            // ── Collateral 1 Verification ─────────────────────────────────────
            $table->decimal('coll_1_vmv', 15, 2)->nullable();        // Verified market value
            $table->string('coll_1_enc', 300)->nullable();           // Encumbrance check notes
            $table->boolean('coll_1_physically_inspected')->default(false);
            $table->boolean('coll_1_ownership_accepted')->default(false);
            $table->boolean('coll_1_pledge_signed')->default(false);
            $table->string('coll_1_photo', 500)->nullable();
            $table->boolean('coll_1_customary_verified')->default(false);

            // ── Collateral 2 Verification ──────────────────────────────────────
            $table->decimal('coll_2_vmv', 15, 2)->nullable();
            $table->string('coll_2_enc', 300)->nullable();
            $table->boolean('coll_2_physically_inspected')->default(false);
            $table->boolean('coll_2_ownership_accepted')->default(false);
            $table->boolean('coll_2_pledge_signed')->default(false);
            $table->string('coll_2_photo', 500)->nullable();
            $table->boolean('coll_2_customary_verified')->default(false);

            // ── Social Standing & Community Verification ──────────────────────
            $table->boolean('lc1_name_confirmed')->default(false);
            $table->boolean('lc1_contact_confirmed')->default(false);
            $table->boolean('lc1_letter_sighted')->default(false);
            $table->boolean('clan_name_confirmed')->default(false);
            $table->boolean('clan_contact_confirmed')->default(false);
            $table->boolean('clan_letter_sighted')->default(false);
            $table->boolean('ref1_contacted')->default(false);
            $table->boolean('ref2_contacted')->default(false);
            $table->integer('ref_consistent_count')->default(0);      // References giving consistent info
            $table->boolean('disputes_reported')->default(false);
            $table->string('residence_stability_evi', 300)->nullable(); // How stability confirmed

            // ── Guarantor 1 Verification ──────────────────────────────────────
            $table->boolean('g1_contact_verified')->default(false);
            $table->decimal('g1_income_verified', 15, 2)->nullable();
            $table->decimal('g1_asset_verified', 15, 2)->nullable();
            $table->boolean('g1_relationship_confirmed')->default(false);
            $table->boolean('g1_willing')->default(false);
            $table->boolean('g1_signed')->default(false);

            // ── Guarantor 2 Verification ──────────────────────────────────────
            $table->boolean('g2_contact_verified')->default(false);
            $table->decimal('g2_income_verified', 15, 2)->nullable();
            $table->decimal('g2_asset_verified', 15, 2)->nullable();
            $table->boolean('g2_relationship_confirmed')->default(false);
            $table->boolean('g2_willing')->default(false);
            $table->boolean('g2_signed')->default(false);

            // ── Policy & Officer Controls ─────────────────────────────────────
            $table->integer('contradiction_count')->default(0);
            $table->boolean('time_constraint')->default(false);
            $table->boolean('temp_trigger')->default(false);          // Reapply / reimage flag
            $table->text('field_recommendation')->nullable();         // FO narrative recommendation
            $table->boolean('physical_visit_confirmed')->default(false);
            $table->text('remote_risk_note')->nullable();
            $table->text('officer_notes')->nullable();

            $table->timestamps();
        });

        try {
            Schema::table('client_loan_field_verifications', function (Blueprint $table) {
                $table->foreign('application_id')
                      ->references('id')->on('client_loan_applications')
                      ->cascadeOnDelete();
                $table->foreign('verified_by')
                      ->references('id')->on('users')
                      ->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // FK type mismatch on some environments — app enforces integrity
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_loan_field_verifications');
    }
};
