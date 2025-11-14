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
        Schema::table('agency', function (Blueprint $table) {
            // Add created_at and updated_at columns if they don't exist
            if (!Schema::hasColumn('agency', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('datecreated');
            }
            if (!Schema::hasColumn('agency', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
        
        // Copy existing datecreated values to created_at
        \DB::statement('UPDATE agency SET created_at = datecreated WHERE created_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agency', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
};
