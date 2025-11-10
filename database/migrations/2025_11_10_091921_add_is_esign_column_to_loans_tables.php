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
        // Add is_esign column to personal_loans table
        Schema::table('personal_loans', function (Blueprint $table) {
            $table->boolean('is_esign')->default(false)->after('assigned_to');
        });
        
        // Add is_esign column to group_loans table  
        Schema::table('group_loans', function (Blueprint $table) {
            $table->boolean('is_esign')->default(false)->after('date_closed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove is_esign column from personal_loans table
        Schema::table('personal_loans', function (Blueprint $table) {
            $table->dropColumn('is_esign');
        });
        
        // Remove is_esign column from group_loans table
        Schema::table('group_loans', function (Blueprint $table) {
            $table->dropColumn('is_esign');
        });
    }
};
