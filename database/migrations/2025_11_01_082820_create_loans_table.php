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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('product_type');
            $table->string('code', 50)->unique();
            $table->string('interest', 10);
            $table->string('period', 10);
            $table->decimal('principal', 15, 2);
            $table->decimal('installment', 15, 2);
            $table->integer('status')->default(0);
            $table->boolean('verified')->default(false);
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('added_by');
            $table->string('trading_file', 1000)->nullable();
            $table->string('bank_file', 1000)->nullable();
            $table->string('business_file', 1000)->nullable();
            $table->integer('repay_strategy')->nullable();
            $table->string('repay_name', 1000)->nullable();
            $table->string('repay_address', 1000)->nullable();
            $table->text('comments')->nullable();
            $table->integer('charge_type')->default(1);
            $table->datetime('date_closed')->nullable();
            $table->integer('sign_code')->default(0);
            $table->string('OLoanID', 100)->nullable();
            $table->text('Rcomments')->nullable();
            $table->boolean('restructured')->default(false);
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamps();
            
            $table->foreign('member_id')->references('id')->on('members');
            $table->foreign('product_type')->references('id')->on('products');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
