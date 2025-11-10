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
        // Only add the column if it doesn't already exist
        if (!Schema::hasColumn('members', 'mobile_pin')) {
            Schema::table('members', function (Blueprint $table) {
                $table->string('mobile_pin', 10)->nullable()->after('password');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('members', 'mobile_pin')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('mobile_pin');
            });
        }
    }
};