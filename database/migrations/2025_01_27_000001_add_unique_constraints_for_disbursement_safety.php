<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add safety constraints to prevent duplicate disbursements.
     */
    public function up(): void
    {
        if (Schema::hasTable('disbursement_txn')) {
            Schema::table('disbursement_txn', function (Blueprint $table) {
                if (!Schema::hasColumn('disbursement_txn', 'disbursement_id')) {
                    $table->unsignedBigInteger('disbursement_id')->nullable()->after('id');
                }
            });

            if (
                Schema::hasColumn('disbursement_txn', 'txnref') &&
                !$this->tableHasIndex('disbursement_txn', 'disbursement_txn_txnref_unique')
            ) {
                Schema::table('disbursement_txn', function (Blueprint $table) {
                    $table->unique('txnref', 'disbursement_txn_txnref_unique');
                });
            }
        }

        if (Schema::hasTable('disbursements') && !$this->tableHasIndex('disbursements', 'disbursements_loan_status_check')) {
            Schema::table('disbursements', function (Blueprint $table) {
                $table->index(['loan_id', 'status'], 'disbursements_loan_status_check');
            });
        }

        if (
            Schema::hasTable('raw_payments') &&
            Schema::hasColumn('raw_payments', 'txn_id') &&
            !$this->tableHasIndex('raw_payments', 'raw_payments_txn_id_unique')
        ) {
            Schema::table('raw_payments', function (Blueprint $table) {
                $table->unique('txn_id', 'raw_payments_txn_id_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->tableHasIndex('disbursement_txn', 'disbursement_txn_txnref_unique')) {
            Schema::table('disbursement_txn', function (Blueprint $table) {
                $table->dropUnique('disbursement_txn_txnref_unique');
            });
        }

        if ($this->tableHasIndex('disbursements', 'disbursements_loan_status_check')) {
            Schema::table('disbursements', function (Blueprint $table) {
                $table->dropIndex('disbursements_loan_status_check');
            });
        }

        if ($this->tableHasIndex('raw_payments', 'raw_payments_txn_id_unique')) {
            Schema::table('raw_payments', function (Blueprint $table) {
                $table->dropUnique('raw_payments_txn_id_unique');
            });
        }
    }

    private function tableHasIndex(string $table, string $index): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        return collect(Schema::getIndexes($table))
            ->contains(fn ($existingIndex) => ($existingIndex['name'] ?? null) === $index);
    }
};
