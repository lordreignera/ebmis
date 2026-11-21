<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Some existing deployments use INT for branches.id while a fresh
        // Laravel installation uses BIGINT. The foreign-key column must have
        // the same type as the referenced column in MySQL.
        $branchIdColumn = 'foreignId';
        if (DB::getDriverName() === 'mysql') {
            $branchId = DB::selectOne("SHOW COLUMNS FROM `branches` WHERE Field = 'id'");
            $branchIdType = strtolower((string) ($branchId->Type ?? ''));
            $branchIdColumn = str_contains($branchIdType, 'bigint')
                ? (str_contains($branchIdType, 'unsigned') ? 'unsignedBigInteger' : 'bigInteger')
                : (str_contains($branchIdType, 'unsigned') ? 'unsignedInteger' : 'integer');
        }

        Schema::create('user_branches', function (Blueprint $table) use ($branchIdColumn) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->{$branchIdColumn}('branch_id');
            $table->timestamps();

            $table->unique(['user_id', 'branch_id']);
            $table->index('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        });

        // Preserve the existing single-branch assignments when introducing the
        // pivot. The users.branch_id column remains the primary/default branch.
        $validBranchIds = DB::table('branches')->pluck('id');

        DB::table('users')
            ->whereNotNull('branch_id')
            ->whereIn('branch_id', $validBranchIds)
            ->orderBy('id')
            ->eachById(function (object $user): void {
                DB::table('user_branches')->insertOrIgnore([
                    'user_id' => $user->id,
                    'branch_id' => $user->branch_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_branches');
    }
};
