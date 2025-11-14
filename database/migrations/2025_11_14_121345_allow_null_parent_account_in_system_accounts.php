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
        Schema::table('system_accounts', function (Blueprint $table) {
            // Modify parent_account to allow NULL values
            $table->integer('parent_account')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_accounts', function (Blueprint $table) {
            // Revert back to NOT NULL if needed
            $table->integer('parent_account')->nullable(false)->change();
        });
    }
};
