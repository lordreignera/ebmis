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
        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->string('payment_date', 20);
            $table->decimal('payment', 15, 2);
            $table->decimal('interest', 15, 2);
            $table->decimal('principal', 15, 2);
            $table->decimal('balance', 15, 2);
            $table->integer('status')->default(0);
            $table->string('date_modified', 100)->nullable();
            $table->text('raw_message')->nullable();
            $table->text('txn_id')->nullable();
            $table->datetime('date_cleared')->nullable();
            $table->timestamps();
            
            // Note: Foreign key will be added later after personal_loans table is created
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_schedules');
    }
};
