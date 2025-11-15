<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add safety constraints to prevent duplicate disbursements
     */
    public function up(): void
    {
        Schema::table('disbursement_txn', function (Blueprint $table) {
            // Add disbursement_id column if not exists
            if (!Schema::hasColumn('disbursement_txn', 'disbursement_id')) {
                $table->unsignedBigInteger('disbursement_id')->nullable()->after('id');
            }
            
            // Add unique index on txnref to prevent duplicate Stanbic requests
            if (!Schema::hasColumn('disbursement_txn', 'txnref') || 
                collect(Schema::getIndexes('disbursement_txn'))->where('name', 'disbursement_txn_txnref_unique')->isEmpty()) {
                $table->unique('txnref', 'disbursement_txn_txnref_unique');
            }
        });

        Schema::table('disbursements', function (Blueprint $table) {
            // Add index on loan_id + status for faster duplicate checking
            if (collect(Schema::getIndexes('disbursements'))->where('name', 'disbursements_loan_status_check')->isEmpty()) {
                $table->index(['loan_id', 'status'], 'disbursements_loan_status_check');
            }
        });

        Schema::table('raw_payments', function (Blueprint $table) {
            // Add unique constraint on txn_id to prevent duplicate tracking
            if (collect(Schema::getIndexes('raw_payments'))->where('name', 'raw_payments_txn_id_unique')->isEmpty()) {
                $table->unique('txn_id', 'raw_payments_txn_id_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disbursement_txn', function (Blueprint $table) {
            $table->dropUnique('disbursement_txn_txnref_unique');
        });

        Schema::table('disbursements', function (Blueprint $table) {
            $table->dropIndex('disbursements_loan_status_check');
        });

        Schema::table('raw_payments', function (Blueprint $table) {
            $table->dropUnique('raw_payments_txn_id_unique');
        });
    }
};
