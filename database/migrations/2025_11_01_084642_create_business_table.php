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
        Schema::create('business', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('name', 60);
            $table->string('reg_date', 30);
            $table->string('reg_no', 40);
            $table->string('tin', 40);
            $table->string('b_type', 30);
            $table->string('pdt_1', 50)->nullable();
            $table->string('pdt_2', 50)->nullable();
            $table->string('pdt_3', 50)->nullable();
            $table->timestamps();
            
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business');
    }
};
