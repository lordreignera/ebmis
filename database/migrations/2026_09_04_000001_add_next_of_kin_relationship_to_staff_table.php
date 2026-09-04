<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('staff') || Schema::hasColumn('staff', 'next_of_kin_relationship')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table) {
            $table->string('next_of_kin_relationship', 100)->nullable()->after('next_of_kin_phone');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('staff') || !Schema::hasColumn('staff', 'next_of_kin_relationship')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('next_of_kin_relationship');
        });
    }
};
