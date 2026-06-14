<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_loan_field_verifications')) {
            $this->addColumnIfMissing('residence_visited', fn (Blueprint $table) => $table->boolean('residence_visited')->default(false));
            $this->addColumnIfMissing('residence_matches_declaration', fn (Blueprint $table) => $table->boolean('residence_matches_declaration')->default(false));
            $this->addColumnIfMissing('lc1_contacted_or_letter_reviewed', fn (Blueprint $table) => $table->boolean('lc1_contacted_or_letter_reviewed')->default(false));
            $this->addColumnIfMissing('lc1_confirms_client', fn (Blueprint $table) => $table->boolean('lc1_confirms_client')->default(false));
            $this->addColumnIfMissing('business_exists', fn (Blueprint $table) => $table->boolean('business_exists')->default(false));
            $this->addColumnIfMissing('business_type_confirmed', fn (Blueprint $table) => $table->boolean('business_type_confirmed')->default(false));
            $this->addColumnIfMissing('business_location_confirmed', fn (Blueprint $table) => $table->boolean('business_location_confirmed')->default(false));
            $this->addColumnIfMissing('business_photos_reviewed', fn (Blueprint $table) => $table->boolean('business_photos_reviewed')->default(false));
            $this->addColumnIfMissing('purchases_book_seen', fn (Blueprint $table) => $table->boolean('purchases_book_seen')->default(false));
            $this->addColumnIfMissing('expense_records_seen', fn (Blueprint $table) => $table->boolean('expense_records_seen')->default(false));
            $this->addColumnIfMissing('g1_commitment_verified', fn (Blueprint $table) => $table->string('g1_commitment_verified', 30)->nullable());
            $this->addColumnIfMissing('g1_pledge_confirmed', fn (Blueprint $table) => $table->boolean('g1_pledge_confirmed')->default(false));
            $this->addColumnIfMissing('g2_commitment_verified', fn (Blueprint $table) => $table->string('g2_commitment_verified', 30)->nullable());
            $this->addColumnIfMissing('g2_pledge_confirmed', fn (Blueprint $table) => $table->boolean('g2_pledge_confirmed')->default(false));
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_loan_field_verifications')) {
            return;
        }

        foreach ([
            'residence_visited',
            'residence_matches_declaration',
            'lc1_contacted_or_letter_reviewed',
            'lc1_confirms_client',
            'business_exists',
            'business_type_confirmed',
            'business_location_confirmed',
            'business_photos_reviewed',
            'purchases_book_seen',
            'expense_records_seen',
            'g1_commitment_verified',
            'g1_pledge_confirmed',
            'g2_commitment_verified',
            'g2_pledge_confirmed',
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
