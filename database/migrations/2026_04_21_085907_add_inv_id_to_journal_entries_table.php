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
        Schema::table('journal_entries', function (Blueprint $table) {
            // Direct reference to investment.id (investor/fund source)
            // fund_id references the funds table (empty for now); inv_id links to investment table
            $table->unsignedBigInteger('inv_id')->nullable()->after('fund_id')
                  ->comment('Investment/investor ID from investment table');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn('inv_id');
        });
    }
};
