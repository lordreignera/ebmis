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
        if (Schema::hasTable('loan_follow_ups')) {
            return;
        }

        Schema::create('loan_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->string('loan_type', 20)->default('personal');
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('follow_up_at');
            $table->string('contact_method', 40);
            $table->string('outcome', 60);
            $table->boolean('willing_to_pay')->default(false);
            $table->date('promise_date')->nullable();
            $table->decimal('promise_amount', 15, 2)->nullable();
            $table->string('next_action', 80)->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->boolean('sms_sent')->default(false);
            $table->text('sms_message')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['loan_type', 'loan_id']);
            $table->index(['assigned_to', 'next_follow_up_date']);
            $table->index(['branch_id', 'follow_up_at']);
            $table->index('outcome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_follow_ups');
    }
};
