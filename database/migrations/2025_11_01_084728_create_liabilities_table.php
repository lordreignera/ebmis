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
        Schema::create('liabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->integer('liability_id');
            $table->integer('liability_type');
            $table->integer('business_id')->nullable();
            $table->integer('value');
            $table->timestamps();
            
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liabilities');
    }
};
