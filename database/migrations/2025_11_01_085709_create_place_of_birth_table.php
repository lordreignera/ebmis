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
        Schema::create('place_of_birth', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('plot_no', 80);
            $table->string('village', 80);
            $table->string('parish', 80);
            $table->string('subcounty', 80);
            $table->string('county', 80);
            $table->unsignedBigInteger('country_id');
            $table->timestamps();
            
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('country_id')->references('id')->on('countries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('place_of_birth');
    }
};
