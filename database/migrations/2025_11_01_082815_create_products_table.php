<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->integer('type');
            $table->integer('loan_type');
            $table->string('description', 200);
            $table->string('max_amt', 15)->nullable();
            $table->string('interest', 15)->nullable();
            $table->integer('period_type');
            $table->integer('added_by');
            $table->timestamp('datecreated')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->integer('isactive')->default(1);
            $table->string('cash_sceurity', 20)->default('25'); // Keep original typo for compatibility
            $table->integer('account');
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
