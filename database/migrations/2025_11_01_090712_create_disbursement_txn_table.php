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
        Schema::create('disbursement_txn', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->string('phone', 20);
            $table->string('amount', 30);
            $table->string('status', 30);
            $table->string('txnref', 40)->nullable();
            $table->string('message', 80);
            $table->text('dump')->nullable();
            $table->text('raw')->nullable();
            $table->text('request')->nullable();
            $table->timestamps();
            
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disbursement_txn');
    }
};
