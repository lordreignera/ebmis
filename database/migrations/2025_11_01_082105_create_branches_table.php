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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('address', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 191)->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('region_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
