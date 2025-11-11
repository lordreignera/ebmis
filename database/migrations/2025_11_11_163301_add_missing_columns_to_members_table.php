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
            // Add status column for member approval workflow
            if (!Schema::hasColumn('members', 'status')) {
                $table->enum('status', ['pending', 'approved', 'suspended', 'rejected'])
                      ->default('pending')
                      ->after('verified');
            }
            
            // Add approval workflow columns
            if (!Schema::hasColumn('members', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('members', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            
            if (!Schema::hasColumn('members', 'approval_notes')) {
                $table->text('approval_notes')->nullable()->after('approved_at');
            }
            
            if (!Schema::hasColumn('members', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approval_notes');
            }
            
            // Add created_at and updated_at timestamps if they don't exist
            if (!Schema::hasColumn('members', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('datecreated');
            }
            
            if (!Schema::hasColumn('members', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $columns = ['status', 'approved_by', 'approved_at', 'approval_notes', 'rejection_reason', 'created_at', 'updated_at'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('members', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
