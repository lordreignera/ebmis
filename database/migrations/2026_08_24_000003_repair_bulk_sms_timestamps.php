<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bulk_sms')) {
            return;
        }

        Schema::table('bulk_sms', function (Blueprint $table) {
            if (! Schema::hasColumn('bulk_sms', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('bulk_sms', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        if (Schema::hasColumn('bulk_sms', 'datecreated')) {
            DB::table('bulk_sms')
                ->whereNull('created_at')
                ->update([
                    'created_at' => DB::raw('datecreated'),
                    'updated_at' => DB::raw('datecreated'),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bulk_sms')) {
            return;
        }

        Schema::table('bulk_sms', function (Blueprint $table) {
            if (Schema::hasColumn('bulk_sms', 'created_at')) {
                $table->dropColumn('created_at');
            }

            if (Schema::hasColumn('bulk_sms', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
