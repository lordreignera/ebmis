<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expenditures') && !Schema::hasColumn('expenditures', 'investment_id')) {
            Schema::table('expenditures', function (Blueprint $table) {
                $table->unsignedBigInteger('investment_id')->nullable()->index()->after('payment_account_id');
            });
        }

        if (Schema::hasTable('expenditure_rollouts') && !Schema::hasColumn('expenditure_rollouts', 'investment_id')) {
            Schema::table('expenditure_rollouts', function (Blueprint $table) {
                $table->unsignedBigInteger('investment_id')->nullable()->index()->after('payment_account_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expenditure_rollouts') && Schema::hasColumn('expenditure_rollouts', 'investment_id')) {
            Schema::table('expenditure_rollouts', function (Blueprint $table) {
                $table->dropColumn('investment_id');
            });
        }

        if (Schema::hasTable('expenditures') && Schema::hasColumn('expenditures', 'investment_id')) {
            Schema::table('expenditures', function (Blueprint $table) {
                $table->dropColumn('investment_id');
            });
        }
    }
};
