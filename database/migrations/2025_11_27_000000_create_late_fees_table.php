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
        Schema::create('late_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('member_id');
            $table->decimal('amount', 15, 2);
            $table->integer('days_overdue');
            $table->integer('periods_overdue')->nullable();
            $table->string('period_type', 20)->nullable()->comment('Daily, Weekly, Monthly');
            $table->date('schedule_due_date');
            $table->date('calculated_date')->comment('Date when late fee was calculated');
            $table->text('calculation_details')->nullable()->comment('JSON: principal, interest, rate, etc');
            $table->integer('status')->default(0)->comment('0-pending, 1-paid, 2-waived, 3-cancelled');
            $table->string('waiver_reason', 255)->nullable();
            $table->datetime('waived_at')->nullable();
            $table->unsignedBigInteger('waived_by')->nullable();
            $table->datetime('paid_at')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('loan_id')->references('id')->on('personal_loans')->onDelete('cascade');
            $table->foreign('schedule_id')->references('id')->on('loan_schedules')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('waived_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index('loan_id');
            $table->index('schedule_id');
            $table->index('member_id');
            $table->index('status');
            $table->index('calculated_date');
            $table->index(['loan_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('late_fees');
    }
};
