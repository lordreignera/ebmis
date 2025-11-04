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
        Schema::create('group_loan_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('member_id');
            $table->decimal('principal', 15, 2);
            $table->unsignedBigInteger('added_by');
            $table->timestamps();
            
            $table->foreign('loan_id')->references('id')->on('group_loans')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_loan_members');
    }
};
