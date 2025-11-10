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
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('loan_id')->nullable();
            $table->integer('fees_type_id');
            $table->integer('payment_type');
            $table->decimal('amount', 15, 2);
            $table->string('description', 100)->nullable();
            $table->unsignedBigInteger('added_by');
            $table->string('payment_status', 100)->nullable();
            $table->text('payment_description')->nullable();
            $table->text('payment_raw')->nullable();
            $table->string('pay_ref', 100)->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
            
            $table->foreign('member_id')->references('id')->on('members');
            // Note: loan_id foreign key will be added later after personal_loans table is created
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
