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
        Schema::table('personal_loans', function (Blueprint $table) {
            // 1 = FLAT RATE (Equal installments - Old System)
            // 2 = DECLINING BALANCE (Decreasing interest - New System - Default)
            $table->tinyInteger('interest_method')->default(2)->after('interest')->comment('1=Flat Rate, 2=Declining Balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_loans', function (Blueprint $table) {
            $table->dropColumn('interest_method');
        });
    }
};
