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
        Schema::table('group_loans', function (Blueprint $table) {
            if (!Schema::hasColumn('group_loans', 'approved_by')) {
                $table->integer('approved_by')->nullable()->after('added_by');
            }

            if (!Schema::hasColumn('group_loans', 'date_approved')) {
                $table->datetime('date_approved')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('group_loans', 'rejected_by')) {
                $table->integer('rejected_by')->nullable()->after('date_approved');
            }

            if (!Schema::hasColumn('group_loans', 'date_rejected')) {
                $table->datetime('date_rejected')->nullable()->after('rejected_by');
            }

            if (!Schema::hasColumn('group_loans', 'assigned_to')) {
                $table->integer('assigned_to')->nullable()->after('date_rejected');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_loans', function (Blueprint $table) {
            $columns = array_filter(
                ['approved_by', 'date_approved', 'rejected_by', 'date_rejected', 'assigned_to'],
                fn ($column) => Schema::hasColumn('group_loans', $column)
            );

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
