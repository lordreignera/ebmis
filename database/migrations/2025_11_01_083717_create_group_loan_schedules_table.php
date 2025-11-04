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
        Schema::create('group_loan_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->string('payment_date', 20);
            $table->decimal('payment', 15, 2);
            $table->decimal('interest', 15, 2);
            $table->decimal('principal', 15, 2);
            $table->decimal('balance', 15, 2);
            $table->integer('status');
            $table->timestamps();
            
            $table->foreign('loan_id')->references('id')->on('group_loans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_loan_schedules');
    }
};
