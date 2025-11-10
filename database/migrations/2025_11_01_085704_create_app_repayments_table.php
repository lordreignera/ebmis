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
        Schema::create('app_repayments', function (Blueprint $table) {
            $table->id();
            $table->integer('type');
            $table->string('details', 60)->nullable();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('schedule_id');
            $table->string('amount', 30);
            $table->unsignedBigInteger('added_by');
            $table->integer('status')->nullable()->default(0);
            $table->text('raw_message')->nullable();
            $table->string('pay_status', 100)->nullable();
            $table->string('txn_id', 100)->nullable();
            $table->timestamps();
            
            // Note: loan_id foreign key will be added later after personal_loans table is created
            $table->foreign('schedule_id')->references('id')->on('loan_schedules')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_repayments');
    }
};
