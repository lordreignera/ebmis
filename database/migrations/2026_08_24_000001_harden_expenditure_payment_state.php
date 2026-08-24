<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expenditures')) {
            return;
        }

        Schema::table('expenditures', function (Blueprint $table) {
            if (!Schema::hasColumn('expenditures', 'investment_debited_at')) {
                $table->timestamp('investment_debited_at')->nullable()->after('investment_id');
            }

            if (!Schema::hasColumn('expenditures', 'staff_payment_period_start')) {
                $table->date('staff_payment_period_start')->nullable()->after('rollout_batch_id');
            }

            if (!Schema::hasColumn('expenditures', 'staff_payment_period_end')) {
                $table->date('staff_payment_period_end')->nullable()->after('staff_payment_period_start');
            }
        });

        Schema::table('expenditures', function (Blueprint $table) {
            if (Schema::hasColumn('expenditures', 'staff_payment_period_start')) {
                $table->index(
                    ['type', 'assigned_user_id', 'staff_payment_period_start', 'staff_payment_period_end'],
                    'expenditures_staff_payment_period_idx'
                );
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('expenditures')) {
            return;
        }

        Schema::table('expenditures', function (Blueprint $table) {
            $table->dropIndex('expenditures_staff_payment_period_idx');
        });

        Schema::table('expenditures', function (Blueprint $table) {
            foreach (['staff_payment_period_end', 'staff_payment_period_start', 'investment_debited_at'] as $column) {
                if (Schema::hasColumn('expenditures', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
