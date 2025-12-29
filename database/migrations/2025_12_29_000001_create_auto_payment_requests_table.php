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
        Schema::create('auto_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('member_id');
            $table->string('phone', 20);
            $table->decimal('amount', 15, 2);
            $table->string('loan_type', 20); // daily, weekly, monthly
            $table->string('status', 20)->default('initiated'); // initiated, completed, failed
            $table->integer('retry_count')->default(0);
            $table->timestamp('initiated_at');
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('late_fee_generated_at')->nullable();
            
            // FlexiPay/API tracking fields
            $table->string('transaction_ref', 50)->nullable(); // Stanbic/Emuria reference
            $table->string('flexipay_ref', 50)->nullable(); // FlexiPay reference
            $table->string('api_status_code', 20)->nullable(); // API status code
            $table->text('api_message')->nullable(); // API response message
            $table->text('api_response')->nullable(); // Full JSON response
            
            $table->timestamps();
            
            $table->index('schedule_id');
            $table->index('loan_id');
            $table->index('status');
            $table->index('next_retry_at');
            $table->index('transaction_ref');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_payment_requests');
    }
};
