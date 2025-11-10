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
        // Add foreign keys for all tables that reference personal loans
        
        // 1. loan_schedules -> personal_loans
        Schema::table('loan_schedules', function (Blueprint $table) {
            $table->foreign('loan_id')->references('id')->on('personal_loans')->onDelete('cascade');
        });
        
        // 2. repayments -> personal_loans
        Schema::table('repayments', function (Blueprint $table) {
            $table->foreign('loan_id')->references('id')->on('personal_loans')->onDelete('cascade');
        });
        
        // 3. disbursements -> personal_loans
        Schema::table('disbursements', function (Blueprint $table) {
            $table->foreign('loan_id')->references('id')->on('personal_loans')->onDelete('cascade');
        });
        
        // 4. guarantors -> personal_loans
        Schema::table('guarantors', function (Blueprint $table) {
            $table->foreign('loan_id')->references('id')->on('personal_loans')->onDelete('cascade');
        });
        
        // 5. fees -> personal_loans (nullable, so set null on delete)
        Schema::table('fees', function (Blueprint $table) {
            $table->foreign('loan_id')->references('id')->on('personal_loans')->onDelete('set null');
        });
        
        // 6. loan_charges -> personal_loans
        Schema::table('loan_charges', function (Blueprint $table) {
            $table->foreign('loan_id')->references('id')->on('personal_loans')->onDelete('cascade');
        });
        
        // 7. app_repayments -> personal_loans
        Schema::table('app_repayments', function (Blueprint $table) {
            $table->foreign('loan_id')->references('id')->on('personal_loans')->onDelete('cascade');
        });
        
        // 8. disbursement_txn -> personal_loans
        Schema::table('disbursement_txn', function (Blueprint $table) {
            $table->foreign('loan_id')->references('id')->on('personal_loans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all the foreign key constraints
        
        Schema::table('loan_schedules', function (Blueprint $table) {
            $table->dropForeign(['loan_id']);
        });
        
        Schema::table('repayments', function (Blueprint $table) {
            $table->dropForeign(['loan_id']);
        });
        
        Schema::table('disbursements', function (Blueprint $table) {
            $table->dropForeign(['loan_id']);
        });
        
        Schema::table('guarantors', function (Blueprint $table) {
            $table->dropForeign(['loan_id']);
        });
        
        Schema::table('fees', function (Blueprint $table) {
            $table->dropForeign(['loan_id']);
        });
        
        Schema::table('loan_charges', function (Blueprint $table) {
            $table->dropForeign(['loan_id']);
        });
        
        Schema::table('app_repayments', function (Blueprint $table) {
            $table->dropForeign(['loan_id']);
        });
        
        Schema::table('disbursement_txn', function (Blueprint $table) {
            $table->dropForeign(['loan_id']);
        });
    }
};
