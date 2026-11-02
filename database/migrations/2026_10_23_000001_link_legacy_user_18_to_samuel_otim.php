<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Link the legacy transaction identity to the verified current user.
     * Historical source rows are intentionally not rewritten.
     */
    public function up(): void
    {
        // Fresh installations and in-memory test databases have no legacy identity to link.
        // Keep the strict identity check for populated databases to avoid misattribution.
        if (!DB::table('users')->exists()) {
            return;
        }

        DB::transaction(function (): void {
            $currentUser = DB::table('users')
                ->where('id', 17)
                ->where('name', 'Samuel Otim')
                ->where('branch_id', 5)
                ->first();

            if (!$currentUser) {
                throw new RuntimeException('Verified current Samuel Otim user (ID 17, branch 5) was not found.');
            }

            $existing = DB::table('user_legacy_mapping')
                ->where('old_user_id', 18)
                ->first();

            if ($existing && (int) $existing->new_user_id !== 17) {
                throw new RuntimeException('Legacy user 18 is already mapped to a different current user.');
            }

            if (!$existing) {
                DB::table('user_legacy_mapping')->insert([
                    'new_user_id' => 17,
                    'old_user_id' => 18,
                    'old_username' => 'Otim',
                    'branch_id' => 5,
                    'user_type' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /**
     * Remove only the mapping created by this migration; source history remains intact.
     */
    public function down(): void
    {
        DB::table('user_legacy_mapping')
            ->where('old_user_id', 18)
            ->where('new_user_id', 17)
            ->delete();
    }
};
