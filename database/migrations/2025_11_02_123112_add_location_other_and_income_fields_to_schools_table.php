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
        Schema::table('schools', function (Blueprint $table) {
            // Location "Other" fields - for when user selects "Other" in dropdown
            $table->string('district_other')->nullable()->after('district');
            $table->string('county_other')->nullable()->after('county');
            $table->string('parish_other')->nullable()->after('parish');
            $table->string('village_other')->nullable()->after('village');
            
            // Income sources and amounts as JSON arrays
            $table->json('income_sources')->nullable()->after('other_income_sources')->comment('Array of income source names');
            $table->json('income_amounts')->nullable()->after('income_sources')->comment('Array of income amounts corresponding to sources');
            
            // Student fees file path (for Excel import)
            $table->string('student_fees_file_path')->nullable()->after('average_tuition_fees_per_term')->comment('Path to uploaded student fees Excel file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'district_other',
                'county_other',
                'parish_other',
                'village_other',
                'income_sources',
                'income_amounts',
                'student_fees_file_path',
            ]);
        });
    }
};
