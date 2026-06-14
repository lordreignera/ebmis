<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expenditures')) {
            Schema::table('expenditures', function (Blueprint $table) {
                if (!Schema::hasColumn('expenditures', 'payment_channel')) {
                    $table->string('payment_channel', 40)->nullable()->after('payment_method');
                }
                if (!Schema::hasColumn('expenditures', 'mobile_money_phone')) {
                    $table->string('mobile_money_phone', 20)->nullable()->after('payment_channel');
                }
                if (!Schema::hasColumn('expenditures', 'mobile_money_network')) {
                    $table->string('mobile_money_network', 20)->nullable()->after('mobile_money_phone');
                }
                if (!Schema::hasColumn('expenditures', 'mobile_money_reference')) {
                    $table->string('mobile_money_reference', 100)->nullable()->after('mobile_money_network');
                }
                if (!Schema::hasColumn('expenditures', 'mobile_money_status')) {
                    $table->string('mobile_money_status', 40)->nullable()->after('mobile_money_reference');
                }
                if (!Schema::hasColumn('expenditures', 'mobile_money_message')) {
                    $table->text('mobile_money_message')->nullable()->after('mobile_money_status');
                }
                if (!Schema::hasColumn('expenditures', 'mobile_money_raw')) {
                    $table->longText('mobile_money_raw')->nullable()->after('mobile_money_message');
                }
                if (!Schema::hasColumn('expenditures', 'payment_initiated_at')) {
                    $table->timestamp('payment_initiated_at')->nullable()->after('mobile_money_raw');
                }
            });
        }

        if (Schema::hasTable('raw_payments') && !Schema::hasColumn('raw_payments', 'expenditure_id')) {
            Schema::table('raw_payments', function (Blueprint $table) {
                if (Schema::hasColumn('raw_payments', 'disbursement_id')) {
                    $table->unsignedBigInteger('expenditure_id')->nullable()->index()->after('disbursement_id');
                } elseif (Schema::hasColumn('raw_payments', 'loan_id')) {
                    $table->unsignedBigInteger('expenditure_id')->nullable()->index()->after('loan_id');
                } else {
                    $table->unsignedBigInteger('expenditure_id')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('raw_payments') && Schema::hasColumn('raw_payments', 'expenditure_id')) {
            Schema::table('raw_payments', function (Blueprint $table) {
                $table->dropColumn('expenditure_id');
            });
        }

        if (Schema::hasTable('expenditures')) {
            Schema::table('expenditures', function (Blueprint $table) {
                foreach ([
                    'payment_channel',
                    'mobile_money_phone',
                    'mobile_money_network',
                    'mobile_money_reference',
                    'mobile_money_status',
                    'mobile_money_message',
                    'mobile_money_raw',
                    'payment_initiated_at',
                ] as $column) {
                    if (Schema::hasColumn('expenditures', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
