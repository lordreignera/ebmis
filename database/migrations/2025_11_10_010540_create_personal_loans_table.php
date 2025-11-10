<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personal_loans', function (Blueprint $table) {
            $table->id();
            $table->integer('member_id');
            $table->integer('product_type');
            $table->string('code', 50);
            $table->string('interest', 10);
            $table->string('period', 10);
            $table->string('principal', 20);
            $table->string('installment', 20);
            $table->integer('status')->comment('0-active,1-complete');
            $table->integer('verified')->comment('0-not,1-yes,2-rejected, 3-rejected disburse');
            $table->integer('added_by');
            $table->timestamp('datecreated')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('trading_file', 1000)->nullable();
            $table->string('bank_file', 1000)->nullable();
            $table->string('business_file', 1000)->nullable();
            $table->integer('repay_strategy')->nullable();
            $table->string('repay_name', 1000)->nullable();
            $table->string('repay_address', 1000)->nullable();
            $table->integer('branch_id');
            $table->text('comments')->nullable();
            $table->integer('charge_type')->default(1)->comment('1-ondisburse, 2-paid');
            $table->datetime('date_closed')->nullable();
            $table->integer('sign_code');
            $table->string('OLoanID', 100)->nullable();
            $table->text('Rcomments')->nullable();
            $table->integer('restructured')->default(0);
            $table->integer('assigned_to')->nullable();
            
            // Set engine and charset
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_loans');
    }
};
