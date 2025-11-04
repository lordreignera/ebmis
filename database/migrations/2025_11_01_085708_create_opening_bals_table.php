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
        Schema::create('opening_bals', function (Blueprint $table) {
            $table->id();
            $table->integer('agency_id');
            $table->string('balance', 30);
            $table->string('bal_date', 30);
            $table->unsignedBigInteger('added_by');
            $table->unsignedBigInteger('branch_id');
            $table->timestamps();
            
            $table->foreign('added_by')->references('id')->on('users');
            $table->foreign('branch_id')->references('id')->on('branches');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opening_bals');
    }
};
