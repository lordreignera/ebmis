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
        Schema::table('members', function (Blueprint $table) {
            // Add unique constraints for better data integrity
            $table->unique('nin', 'members_nin_unique');
            $table->unique('contact', 'members_contact_unique');
            
            // Add indexes for better performance on searches
            $table->index(['nin', 'soft_delete'], 'members_nin_soft_delete_index');
            $table->index(['contact', 'soft_delete'], 'members_contact_soft_delete_index');
            $table->index(['email', 'soft_delete'], 'members_email_soft_delete_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique('members_nin_unique');
            $table->dropUnique('members_contact_unique');
            $table->dropIndex('members_nin_soft_delete_index');
            $table->dropIndex('members_contact_soft_delete_index');
            $table->dropIndex('members_email_soft_delete_index');
        });
    }
};
