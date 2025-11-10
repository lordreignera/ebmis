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
        // Set MySQL session timezone to East African Time (UTC+3)
        try {
            DB::statement("SET time_zone = '+03:00'");
            
            // Update existing timestamp columns to ensure they're interpreted correctly
            // Note: This assumes timestamps were stored in UTC and need timezone awareness
            
            // Log the timezone change
            \Log::info('MySQL timezone configured for East African Time (UTC+3)');
            
        } catch (\Exception $e) {
            \Log::warning('Could not set MySQL timezone: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to UTC
        try {
            DB::statement("SET time_zone = '+00:00'");
            \Log::info('MySQL timezone reverted to UTC');
        } catch (\Exception $e) {
            \Log::warning('Could not revert MySQL timezone: ' . $e->getMessage());
        }
    }
};
