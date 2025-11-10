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
        Schema::create('repayments', function (Blueprint $table) {
            $table->id();
            $table->integer('type');
            $table->string('details', 60)->nullable();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('schedule_id');
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('added_by');
            $table->integer('status')->default(0);
            $table->string('platform', 10)->nullable();
            $table->text('raw_message')->nullable();
            $table->string('pay_status', 191)->nullable();
            $table->string('txn_id', 191)->nullable();
            $table->text('pay_message')->nullable();
            $table->timestamps();
            
            // Note: Foreign keys will be added later after personal_loans table is created
            $table->foreign('schedule_id')->references('id')->on('loan_schedules')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repayments');
    }
};
