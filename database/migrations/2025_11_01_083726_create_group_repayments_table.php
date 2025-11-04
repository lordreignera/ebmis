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
        Schema::create('group_repayments', function (Blueprint $table) {
            $table->id();
            $table->integer('type');
            $table->string('details', 60)->nullable();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('schedule_id');
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('added_by');
            $table->timestamps();
            
            $table->foreign('loan_id')->references('id')->on('group_loans')->onDelete('cascade');
            $table->foreign('schedule_id')->references('id')->on('group_loan_schedules')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_repayments');
    }
};
