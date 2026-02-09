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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id('Id');
            
            // Journal identification
            $table->string('journal_number', 50)->unique()->comment('Auto-generated: JE-YYYYMMDD-XXXX');
            $table->date('transaction_date')->index();
            
            // Reference tracking (what created this entry)
            $table->string('reference_type', 50)->nullable()->comment('Disbursement, Repayment, Fee, Adjustment, etc.');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('ID from reference table');
            
            // Tagging for advanced reporting (EBIMS-compliant)
            $table->unsignedBigInteger('cost_center_id')->nullable()->comment('Branch ID');
            $table->unsignedBigInteger('product_id')->nullable()->comment('Loan Product ID');
            $table->unsignedBigInteger('officer_id')->nullable()->comment('Loan Officer User ID');
            $table->unsignedBigInteger('fund_id')->nullable()->comment('Fund/Donor ID');
            
            // Financial summary
            $table->text('narrative')->comment('Overall description of transaction');
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            
            // Status and control
            $table->enum('status', ['pending', 'posted', 'reversed'])->default('posted')->index();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason', 500)->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('cost_center_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('officer_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('fund_id')->references('id')->on('funds')->onDelete('set null');
            $table->foreign('posted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reversed_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for reporting
            $table->index(['reference_type', 'reference_id']);
            $table->index(['cost_center_id', 'transaction_date']);
            $table->index(['product_id', 'transaction_date']);
            $table->index(['officer_id', 'transaction_date']);
            $table->index(['fund_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
