<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_loan_field_verifications')) {
            $this->addColumnIfMissing('residence_stability_rating', fn (Blueprint $table) => $table->string('residence_stability_rating', 30)->nullable());
            $this->addColumnIfMissing('business_stability_rating', fn (Blueprint $table) => $table->string('business_stability_rating', 30)->nullable());
            $this->addColumnIfMissing('records_credibility', fn (Blueprint $table) => $table->string('records_credibility', 30)->nullable());
            $this->addColumnIfMissing('crb_max_arrears_days', fn (Blueprint $table) => $table->integer('crb_max_arrears_days')->nullable());
            $this->addColumnIfMissing('coll_1_document_seen', fn (Blueprint $table) => $table->boolean('coll_1_document_seen')->default(false));
            $this->addColumnIfMissing('coll_1_legal_enforceability', fn (Blueprint $table) => $table->string('coll_1_legal_enforceability', 30)->nullable());
            $this->addColumnIfMissing('coll_2_document_seen', fn (Blueprint $table) => $table->boolean('coll_2_document_seen')->default(false));
            $this->addColumnIfMissing('coll_2_legal_enforceability', fn (Blueprint $table) => $table->string('coll_2_legal_enforceability', 30)->nullable());
            $this->addColumnIfMissing('pledge_explained_to_client', fn (Blueprint $table) => $table->boolean('pledge_explained_to_client')->default(false));
            $this->addColumnIfMissing('client_understood_pledge', fn (Blueprint $table) => $table->boolean('client_understood_pledge')->default(false));
            $this->addColumnIfMissing('pledge_witness_present', fn (Blueprint $table) => $table->boolean('pledge_witness_present')->default(false));
            $this->addColumnIfMissing('recommended_product_id', fn (Blueprint $table) => $table->unsignedBigInteger('recommended_product_id')->nullable()->index());
            $this->addColumnIfMissing('recommended_amount', fn (Blueprint $table) => $table->decimal('recommended_amount', 15, 2)->nullable());
            $this->addColumnIfMissing('recommended_tenure_periods', fn (Blueprint $table) => $table->integer('recommended_tenure_periods')->nullable());
            $this->addColumnIfMissing('officer_confidence', fn (Blueprint $table) => $table->string('officer_confidence', 30)->nullable());
        }

        if (Schema::hasTable('loan_policy_controls')) {
            $now = now();
            foreach ([
                [
                    'key' => 'COL_MULT',
                    'label' => 'Collateral Coverage Multiple',
                    'description' => 'Minimum verified collateral FSV to proposed loan amount. Workbook policy requires 3.0x.',
                    'value' => 3.0000,
                    'format' => 'multiplier',
                ],
                [
                    'key' => 'MIN_VSS',
                    'label' => 'Minimum VSS Score',
                    'description' => 'Default minimum Verification Strength Score gate.',
                    'value' => 65.0000,
                    'format' => 'score',
                ],
                [
                    'key' => 'MIN_VSS_WEEKLY',
                    'label' => 'Weekly Minimum VSS Score',
                    'description' => 'Minimum Verification Strength Score for weekly or daily repayment applications.',
                    'value' => 65.0000,
                    'format' => 'score',
                ],
                [
                    'key' => 'MIN_VSS_MONTHLY',
                    'label' => 'Monthly Minimum VSS Score',
                    'description' => 'Minimum Verification Strength Score for monthly repayment applications.',
                    'value' => 75.0000,
                    'format' => 'score',
                ],
                [
                    'key' => 'MAX_ARREARS_DAYS',
                    'label' => 'Maximum External Arrears Days',
                    'description' => 'Maximum verified external arrears days allowed before the arrears gate blocks.',
                    'value' => 30.0000,
                    'format' => 'integer',
                ],
            ] as $control) {
                DB::table('loan_policy_controls')->updateOrInsert(
                    ['key' => $control['key']],
                    $control + ['created_at' => $now, 'updated_at' => $now]
                );
            }

            Cache::forget('loan_policy_controls');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_loan_field_verifications')) {
            return;
        }

        foreach ([
            'residence_stability_rating',
            'business_stability_rating',
            'records_credibility',
            'crb_max_arrears_days',
            'coll_1_document_seen',
            'coll_1_legal_enforceability',
            'coll_2_document_seen',
            'coll_2_legal_enforceability',
            'pledge_explained_to_client',
            'client_understood_pledge',
            'pledge_witness_present',
            'recommended_product_id',
            'recommended_amount',
            'recommended_tenure_periods',
            'officer_confidence',
        ] as $column) {
            if (Schema::hasColumn('client_loan_field_verifications', $column)) {
                Schema::table('client_loan_field_verifications', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    private function addColumnIfMissing(string $column, callable $definition): void
    {
        if (Schema::hasColumn('client_loan_field_verifications', $column)) {
            return;
        }

        Schema::table('client_loan_field_verifications', function (Blueprint $table) use ($definition) {
            $definition($table);
        });
    }
};
