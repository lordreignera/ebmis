<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repayments', function (Blueprint $table) {
            if (!Schema::hasColumn('repayments', 'date_created')) {
                $table->timestamp('date_created')->nullable();
            }

            if (!Schema::hasColumn('repayments', 'payment_status')) {
                $table->string('payment_status', 100)->nullable();
            }

            if (!Schema::hasColumn('repayments', 'transaction_reference')) {
                $table->string('transaction_reference', 100)->nullable();
            }

            if (!Schema::hasColumn('repayments', 'payment_phone')) {
                $table->string('payment_phone', 20)->nullable();
            }

            if (!Schema::hasColumn('repayments', 'original_amount')) {
                $table->decimal('original_amount', 15, 2)->nullable();
            }

            if (!Schema::hasColumn('repayments', 'payment_raw')) {
                $table->text('payment_raw')->nullable();
            }

            if (!Schema::hasColumn('repayments', 'medium')) {
                $table->unsignedTinyInteger('medium')->nullable();
            }

            if (!Schema::hasColumn('repayments', 'network')) {
                $table->string('network', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('repayments', function (Blueprint $table) {
            $columns = [
                'date_created',
                'payment_status',
                'transaction_reference',
                'payment_phone',
                'original_amount',
                'payment_raw',
                'medium',
                'network',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('repayments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
