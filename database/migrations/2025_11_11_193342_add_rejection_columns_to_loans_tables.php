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
        // Add rejection columns to personal_loans table if they don't exist
        if (Schema::hasTable('personal_loans')) {
            Schema::table('personal_loans', function (Blueprint $table) {
                if (!Schema::hasColumn('personal_loans', 'rejected_by')) {
                    $table->unsignedBigInteger('rejected_by')->nullable();
                }
                if (!Schema::hasColumn('personal_loans', 'date_rejected')) {
                    $table->datetime('date_rejected')->nullable();
                }
            });
        }

        // Add rejection columns to group_loans table if they don't exist
        if (Schema::hasTable('group_loans')) {
            Schema::table('group_loans', function (Blueprint $table) {
                if (!Schema::hasColumn('group_loans', 'rejected_by')) {
                    $table->unsignedBigInteger('rejected_by')->nullable();
                }
                if (!Schema::hasColumn('group_loans', 'date_rejected')) {
                    $table->datetime('date_rejected')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove rejection columns from personal_loans table
        if (Schema::hasTable('personal_loans')) {
            Schema::table('personal_loans', function (Blueprint $table) {
                if (Schema::hasColumn('personal_loans', 'rejected_by')) {
                    $table->dropColumn('rejected_by');
                }
                if (Schema::hasColumn('personal_loans', 'date_rejected')) {
                    $table->dropColumn('date_rejected');
                }
            });
        }

        // Remove rejection columns from group_loans table
        if (Schema::hasTable('group_loans')) {
            Schema::table('group_loans', function (Blueprint $table) {
                if (Schema::hasColumn('group_loans', 'rejected_by')) {
                    $table->dropColumn('rejected_by');
                }
                if (Schema::hasColumn('group_loans', 'date_rejected')) {
                    $table->dropColumn('date_rejected');
                }
            });
        }
    }
};
