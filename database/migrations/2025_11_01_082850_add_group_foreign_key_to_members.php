<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, clean up invalid group_id references
        // Set group_id to NULL where it's 0 or references non-existent groups
        $this->cleanUpInvalidGroupReferences();
        
        Schema::table('members', function (Blueprint $table) {
            // Add the foreign key constraint for group_id
            // This migration runs after the groups table is created
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('set null');
        });
    }
    
    /**
     * Clean up invalid group_id references before adding foreign key
     */
    private function cleanUpInvalidGroupReferences(): void
    {
        // Get all valid group IDs
        $validGroupIds = DB::table('groups')->pluck('id')->toArray();
        
        // Count members with invalid group_ids
        $invalidCount = DB::table('members')
            ->where(function($query) use ($validGroupIds) {
                $query->where('group_id', 0)
                      ->orWhere('group_id', '<', 1);
                if (!empty($validGroupIds)) {
                    $query->orWhereNotIn('group_id', $validGroupIds);
                }
            })
            ->whereNotNull('group_id')
            ->count();
            
        echo "Found {$invalidCount} members with invalid group_id references. Setting to NULL...\n";
        
        // Set invalid group_ids to NULL
        DB::table('members')
            ->where(function($query) use ($validGroupIds) {
                $query->where('group_id', 0)
                      ->orWhere('group_id', '<', 1);
                if (!empty($validGroupIds)) {
                    $query->orWhereNotIn('group_id', $validGroupIds);
                }
            })
            ->whereNotNull('group_id')
            ->update(['group_id' => null]);
            
        echo "Cleaned up invalid group_id references.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
        });
    }
};