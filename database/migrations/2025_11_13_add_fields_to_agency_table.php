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
            if (!Schema::hasColumn('agency', 'code')) {
                $table->string('code', 50)->nullable()->after('name');
            }
            if (!Schema::hasColumn('agency', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('code');
            }
            if (!Schema::hasColumn('agency', 'phone')) {
                $table->string('phone', 20)->nullable()->after('contact_person');
            }
            if (!Schema::hasColumn('agency', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('agency', 'location')) {
                $table->text('location')->nullable()->after('email');
            }
            if (!Schema::hasColumn('agency', 'description')) {
                $table->text('description')->nullable()->after('location');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agency', function (Blueprint $table) {
            $table->dropColumn([
                'code',
                'contact_person',
                'phone',
                'email',
                'location',
                'description'
            ]);
        });
    }
};
