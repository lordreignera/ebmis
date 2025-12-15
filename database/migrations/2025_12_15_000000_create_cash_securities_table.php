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
        Schema::create('cash_securities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('loan_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->integer('payment_type')->comment('1=Mobile Money, 2=Cash, 3=Bank Transfer');
            $table->string('payment_method', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('pay_ref', 100)->nullable();
            $table->integer('status')->default(0)->comment('0=Pending, 1=Paid, 2=Failed');
            $table->string('payment_status', 100)->nullable();
            $table->text('payment_description')->nullable();
            $table->text('payment_raw')->nullable();
            $table->string('payment_phone', 20)->nullable();
            $table->string('transaction_reference', 100)->nullable();
            $table->unsignedBigInteger('added_by');
            $table->timestamp('datecreated')->useCurrent();
            $table->timestamps();
            
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('set null');
            $table->foreign('added_by')->references('id')->on('users')->onDelete('restrict');
            
            $table->index(['member_id', 'status']);
            $table->index('loan_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_securities');
    }
};
