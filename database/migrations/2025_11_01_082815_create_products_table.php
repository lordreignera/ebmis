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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->integer('type');
            $table->integer('loan_type');
            $table->string('description', 191)->nullable();
            $table->string('max_amt', 15)->nullable();
            $table->string('interest', 15)->nullable();
            $table->integer('period_type');
            $table->string('cash_security', 20)->default('25');
            $table->integer('account')->nullable();
            $table->boolean('isactive')->default(true);
            $table->unsignedBigInteger('added_by');
            $table->timestamps();
            
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
