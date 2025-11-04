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
        Schema::create('disbursements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->integer('loan_type');
            $table->decimal('amount', 15, 2);
            $table->string('comments', 150)->nullable();
            $table->integer('payment_type')->nullable();
            $table->string('account_name', 150)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->integer('inv_id')->nullable();
            $table->integer('status')->default(0);
            $table->string('reject_comments', 100)->nullable();
            $table->unsignedBigInteger('added_by');
            $table->timestamp('date_approved')->nullable();
            $table->string('medium', 20)->nullable();
            $table->timestamps();
            
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disbursements');
    }
};
