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
        Schema::create('savings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('branch_id');
            $table->integer('pdt_id');
            $table->decimal('value', 15, 2);
            $table->string('sperson', 70)->nullable();
            $table->string('sdate', 17)->nullable();
            $table->string('description', 100)->nullable();
            $table->unsignedBigInteger('added_by');
            $table->integer('status')->default(0);
            $table->string('platform', 10)->nullable();
            $table->string('txn_id', 500)->nullable();
            $table->string('pay_status', 100)->nullable();
            $table->text('pay_message')->nullable();
            $table->timestamps();
            
            $table->foreign('member_id')->references('id')->on('members');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings');
    }
};
