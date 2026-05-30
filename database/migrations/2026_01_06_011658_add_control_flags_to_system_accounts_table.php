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
        if (!Schema::hasColumn('system_accounts', 'sub_code')) {
            Schema::table('system_accounts', function (Blueprint $table) {
                $table->string('sub_code', 50)->nullable()->after('code')->comment('Sub-account code for hierarchical structure');
            });
        }

        if (!Schema::hasColumn('system_accounts', 'category')) {
            Schema::table('system_accounts', function (Blueprint $table) {
                $table->string('category', 50)->nullable()->after('accountSubType')->comment('Asset, Liability, Income, Expense');
            });
        }

        if (!Schema::hasColumn('system_accounts', 'is_cash_bank')) {
            Schema::table('system_accounts', function (Blueprint $table) {
                $table->boolean('is_cash_bank')->default(false)->after('category')->comment('TRUE if this is a cash/bank account');
            });
        }

        if (!Schema::hasColumn('system_accounts', 'is_clearing')) {
            Schema::table('system_accounts', function (Blueprint $table) {
                $table->boolean('is_clearing')->default(false)->after('is_cash_bank')->comment('TRUE if this is a clearing/transit account');
            });
        }

        if (!Schema::hasColumn('system_accounts', 'is_loan_receivable')) {
            Schema::table('system_accounts', function (Blueprint $table) {
                $table->boolean('is_loan_receivable')->default(false)->after('is_clearing')->comment('TRUE if this is a loan receivable account');
            });
        }

        if (!Schema::hasColumn('system_accounts', 'allow_manual_posting')) {
            Schema::table('system_accounts', function (Blueprint $table) {
                $table->boolean('allow_manual_posting')->default(true)->after('is_loan_receivable')->comment('FALSE = system-controlled, no manual posting allowed');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // These columns may exist on the original table in newer installs, so avoid
        // dropping them here.
    }
};
