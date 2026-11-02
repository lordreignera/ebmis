<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investors', function (Blueprint $table) {
            if (!Schema::hasColumn('investors', 'password')) {
                $table->string('password')->nullable()->after('email');
            }

            if (!Schema::hasColumn('investors', 'remember_token')) {
                $table->rememberToken();
            }

            if (!Schema::hasColumn('investors', 'portal_status')) {
                $table->string('portal_status', 20)->nullable()->index();
            }

            if (!Schema::hasColumn('investors', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->index();
            }

            if (!Schema::hasColumn('investors', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }

            if (!Schema::hasColumn('investors', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->index();
            }

            if (!Schema::hasColumn('investors', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable();
            }

            if (!Schema::hasColumn('investors', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('investors', function (Blueprint $table) {
            $columns = array_filter([
                'password',
                'remember_token',
                'portal_status',
                'approved_by',
                'approved_at',
                'rejected_by',
                'rejected_at',
                'rejection_reason',
            ], fn (string $column): bool => Schema::hasColumn('investors', $column));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
