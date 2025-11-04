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
        Schema::create('business_address', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id');
            $table->string('street', 50);
            $table->string('plot_no', 50);
            $table->string('house_no', 50);
            $table->string('cell', 50);
            $table->string('ward', 50);
            $table->string('division', 50);
            $table->string('district', 50);
            $table->string('country', 15);
            $table->string('tel_no', 30);
            $table->string('mobile_no', 30);
            $table->string('fixed_line', 30);
            $table->string('email', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_address');
    }
};
