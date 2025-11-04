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
        Schema::table('users', function (Blueprint $table) {
            // Multi-tenant user type
            $table->enum('user_type', ['super_admin', 'school', 'branch'])->default('branch')->after('email');
            
            // Relationships
            $table->foreignId('school_id')->nullable()->after('user_type')->constrained('schools')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->after('school_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('region_id')->nullable()->after('branch_id')->constrained('regions')->onDelete('set null');
            
            // Status and approval
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('active')->after('region_id');
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            
            // Additional fields
            $table->string('phone', 50)->nullable()->after('approved_at');
            $table->text('address')->nullable()->after('phone');
            $table->string('designation', 100)->nullable()->after('address');
            
            // Indexes for performance
            $table->index(['user_type', 'status']);
            $table->index(['school_id', 'status']);
            $table->index(['branch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['region_id']);
            $table->dropForeign(['approved_by']);
            
            $table->dropIndex(['user_type', 'status']);
            $table->dropIndex(['school_id', 'status']);
            $table->dropIndex(['branch_id', 'status']);
            
            $table->dropColumn([
                'user_type', 'school_id', 'branch_id', 'region_id',
                'status', 'approved_by', 'approved_at',
                'phone', 'address', 'designation'
            ]);
        });
    }
};
