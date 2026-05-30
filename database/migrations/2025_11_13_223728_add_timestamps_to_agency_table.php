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
            if (!Schema::hasColumn('agency', 'created_at')) {
                $column = $table->timestamp('created_at')->nullable();

                if (Schema::hasColumn('agency', 'datecreated')) {
                    $column->after('datecreated');
                }
            }

            if (!Schema::hasColumn('agency', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });

        if (Schema::hasColumn('agency', 'datecreated') && Schema::hasColumn('agency', 'created_at')) {
            \DB::statement('UPDATE agency SET created_at = datecreated WHERE created_at IS NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_values(array_filter(['created_at', 'updated_at'], function ($column) {
            return Schema::hasColumn('agency', $column);
        }));

        if (!empty($columns)) {
            Schema::table('agency', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
