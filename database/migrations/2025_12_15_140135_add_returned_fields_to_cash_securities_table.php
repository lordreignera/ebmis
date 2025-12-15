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
        Schema::table('cash_securities', function (Blueprint $table) {
            $table->tinyInteger('returned')->default(0)->after('status')->comment('0=Not Returned, 1=Returned');
            $table->datetime('returned_at')->nullable()->after('returned');
            $table->string('return_transaction_reference')->nullable()->after('returned_at');
            $table->text('return_payment_raw')->nullable()->after('return_transaction_reference');
            $table->string('return_payment_status')->nullable()->after('return_payment_raw');
            $table->unsignedBigInteger('returned_by')->nullable()->after('return_payment_status');
            
            $table->foreign('returned_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_securities', function (Blueprint $table) {
            $table->dropForeign(['returned_by']);
            $table->dropColumn([
                'returned',
                'returned_at',
                'return_transaction_reference',
                'return_payment_raw',
                'return_payment_status',
                'returned_by'
            ]);
        });
    }
};
