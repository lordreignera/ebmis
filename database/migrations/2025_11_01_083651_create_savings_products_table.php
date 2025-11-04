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
        Schema::create('savings_products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 120);
            $table->string('interest', 30);
            $table->string('min_amt', 30);
            $table->string('max_amt', 30);
            $table->string('charge', 11)->nullable();
            $table->string('description', 150);
            $table->integer('account');
            $table->boolean('isactive')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_products');
    }
};
