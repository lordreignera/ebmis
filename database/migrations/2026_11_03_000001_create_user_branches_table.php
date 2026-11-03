<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'branch_id']);
            $table->index('branch_id');
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
