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
        Schema::create('savings_withdraw', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('amount', 30);
            $table->string('wperson', 70)->nullable();
            $table->string('wdate', 60)->nullable();
            $table->string('description', 100)->nullable();
            $table->unsignedBigInteger('added_by');
            $table->string('txn_id', 200)->nullable();
            $table->text('raw_message')->nullable();
            $table->string('pay_status', 100)->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
            
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_withdraw');
    }
};
