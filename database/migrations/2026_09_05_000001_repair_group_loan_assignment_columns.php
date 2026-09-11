<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('group_loans')) {
            return;
        }

        Schema::table('group_loans', function (Blueprint $table) {
            if (!Schema::hasColumn('group_loans', 'approved_by')) {
                $table->integer('approved_by')->nullable();
            }

            if (!Schema::hasColumn('group_loans', 'date_approved')) {
                $table->dateTime('date_approved')->nullable();
            }

            if (!Schema::hasColumn('group_loans', 'rejected_by')) {
                $table->integer('rejected_by')->nullable();
            }

            if (!Schema::hasColumn('group_loans', 'date_rejected')) {
                $table->dateTime('date_rejected')->nullable();
            }

            if (!Schema::hasColumn('group_loans', 'assigned_to')) {
                $table->integer('assigned_to')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        // Intentionally left blank. This repair migration must not drop columns
        // that may have existed before it ran.
    }
};
