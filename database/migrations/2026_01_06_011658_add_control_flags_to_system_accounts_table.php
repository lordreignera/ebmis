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
        Schema::table('system_accounts', function (Blueprint $table) {
            $table->string('sub_code', 50)->nullable()->after('code')->comment('Sub-account code for hierarchical structure');
            $table->string('category', 50)->nullable()->after('accountSubType')->comment('Asset, Liability, Income, Expense');
            $table->boolean('is_cash_bank')->default(false)->after('category')->comment('TRUE if this is a cash/bank account');
            $table->boolean('is_clearing')->default(false)->after('is_cash_bank')->comment('TRUE if this is a clearing/transit account');
            $table->boolean('is_loan_receivable')->default(false)->after('is_clearing')->comment('TRUE if this is a loan receivable account');
            $table->boolean('allow_manual_posting')->default(true)->after('is_loan_receivable')->comment('FALSE = system-controlled, no manual posting allowed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'sub_code',
                'category',
                'is_cash_bank',
                'is_clearing',
                'is_loan_receivable',
                'allow_manual_posting'
            ]);
        });
    }
};
