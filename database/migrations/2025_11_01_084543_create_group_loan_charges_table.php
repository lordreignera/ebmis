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
        Schema::create('group_loan_charges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->string('charge_name', 70);
            $table->integer('charge_type');
            $table->string('charge_value', 20);
            $table->string('actual_value', 20);
            $table->unsignedBigInteger('added_by');
            $table->timestamps();
            
            $table->foreign('loan_id')->references('id')->on('group_loans')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_loan_charges');
    }
};
