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
        Schema::create('school_loans', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys - School must be approved
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->integer('product_type');
            $table->foreign('product_type')->references('id')->on('products')->onDelete('restrict');
            $table->integer('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('restrict');
            $table->foreignId('added_by')->constrained('users')->onDelete('restrict');
            
            // Loan Basic Information
            $table->string('code', 50)->unique();
            $table->string('interest', 10);
            $table->string('period', 10)->comment('Number of installments');
            $table->decimal('principal', 15, 2);
            $table->decimal('installment', 15, 2)->comment('Maximum installment amount');
            
            // Loan Status
            $table->integer('status')->default(0)->comment('0=Pending, 1=Approved, 2=Disbursed, 3=Completed, 4=Rejected');
            $table->integer('verified')->default(0)->comment('0=not verified, 1=verified, 2=rejected, 3=rejected disburse');
            
            // Repayment Details
            $table->integer('repay_strategy')->nullable()->comment('1=School Fees Collection, 2=School Account');
            $table->string('business_name', 1000)->nullable()->comment('School/Institution name');
            $table->string('business_contact', 1000)->nullable()->comment('School contact address');
            $table->enum('repay_period', ['daily', 'weekly', 'monthly'])->default('monthly');
            
            // Supporting Documents
            $table->string('business_license', 1000)->nullable()->comment('School registration/license');
            $table->string('bank_statement', 1000)->nullable()->comment('School bank statements');
            $table->string('business_photos', 1000)->nullable()->comment('School premises photos');
            
            // Charges & Fees
            $table->integer('charge_type')->default(1)->comment('1=Deduct from disbursement, 2=Upfront payment');
            
            // Approval & Rejection Tracking
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('date_approved')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('date_rejected')->nullable();
            $table->text('Rcomments')->nullable()->comment('Rejection comments');
            
            // Closure & Restructuring
            $table->datetime('date_closed')->nullable();
            $table->integer('restructured')->default(0)->comment('0=No, 1=Yes');
            
            // eSign Integration
            $table->boolean('is_esign')->default(false);
            $table->integer('sign_code')->nullable();
            
            // Assignment
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            
            // Additional Fields
            $table->text('comments')->nullable()->comment('Internal comments');
            $table->string('OLoanID', 100)->nullable()->comment('Original Loan ID if migrated');
            $table->text('loan_purpose')->nullable()->comment('Purpose of the loan');
            
            // Timestamps
            $table->timestamp('datecreated')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['school_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index('product_type');
            $table->index('status');
            $table->index('datecreated');
            
            // Set engine and charset
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_loans');
    }
};
