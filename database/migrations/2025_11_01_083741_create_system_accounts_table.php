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
        Schema::create('system_accounts', function (Blueprint $table) {
            $table->id('Id');
            $table->string('code', 100);
            $table->string('sub_code', 100)->nullable();
            $table->string('name', 500);
            $table->string('category', 50); // Asset, Liability, Equity, Income, Expense
            $table->string('accountType', 100)->nullable();
            $table->string('accountSubType', 100)->nullable();
            $table->string('currency', 10)->default('UGX');
            $table->text('description')->nullable();
            $table->integer('parent_account')->default(0);
            $table->double('running_balance')->default(0);
            
            // Control flags for accounting automation
            $table->boolean('is_cash_bank')->default(false);
            $table->boolean('is_clearing')->default(false);
            $table->boolean('is_loan_receivable')->default(false);
            $table->boolean('allow_manual_posting')->default(true);
            
            $table->unsignedBigInteger('added_by')->nullable();
            $table->integer('status')->default(1);
            $table->timestamp('date_created')->nullable();
            
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_accounts');
    }
};
