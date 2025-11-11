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
            // Add mobile_pin column
            if (!Schema::hasColumn('members', 'mobile_pin')) {
                $table->string('mobile_pin', 10)->nullable()->after('password');
            }
            
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
        });
        
        // Add foreign key for approved_by if it doesn't exist
        if (Schema::hasColumn('members', 'approved_by')) {
            try {
                Schema::table('members', function (Blueprint $table) {
                    $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist, ignore
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Drop foreign key first if it exists
            try {
                $table->dropForeign(['approved_by']);
            } catch (\Exception $e) {
                // Ignore if doesn't exist
            }
            
            // Drop columns
            $columns = ['mobile_pin', 'status', 'approved_by', 'approved_at', 'approval_notes', 'rejection_reason'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('members', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
