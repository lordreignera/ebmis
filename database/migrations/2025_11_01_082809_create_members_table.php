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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->nullable()->index();
            $table->string('fname', 80);
            $table->string('lname', 80);
            $table->string('mname', 191)->nullable();
            $table->string('nin', 80);
            $table->string('contact', 80);
            $table->string('alt_contact', 191)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('plot_no', 191)->nullable();
            $table->string('village', 191)->nullable();
            $table->string('parish', 191)->nullable();
            $table->string('subcounty', 191)->nullable();
            $table->string('county', 191)->nullable();
            $table->unsignedBigInteger('country_id');
            $table->string('gender', 20)->nullable();
            $table->string('dob', 20)->nullable();
            $table->string('fixed_line', 191)->nullable();
            $table->boolean('verified')->default(false);
            
            // Member approval workflow fields
            $table->enum('status', ['pending', 'approved', 'suspended', 'rejected'])
                  ->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->string('comments', 150)->nullable();
            $table->integer('member_type');
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->string('pp_file', 1000)->nullable();
            $table->string('id_file', 1000)->nullable();
            $table->boolean('soft_delete')->default(false);
            $table->unsignedBigInteger('del_user')->nullable();
            $table->string('del_comments', 100)->nullable();
            $table->unsignedBigInteger('added_by');
            $table->string('password', 100)->nullable();
            
            // Mobile PIN field
            $table->string('mobile_pin', 10)->nullable();
            
            $table->timestamps();
            
            // Legacy datecreated field for backwards compatibility with old data
            $table->datetime('datecreated')->nullable();
            
            // Foreign key constraints
            $table->foreign('country_id')->references('id')->on('countries');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('added_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            // Note: group_id foreign key is added in a separate migration after groups table is created
        });
        
        // After table creation, handle data migration for imported old data
        $this->handleDataMigration();
    }
    
    /**
     * Handle data migration and field mapping for old imported data
     */
    private function handleDataMigration(): void
    {
        // Check if there's any data in the table (from imported backup)
        $hasData = DB::table('members')->exists();
        
        if ($hasData) {
            // Map datecreated to Laravel timestamps if datecreated exists but timestamps are null
            DB::statement("UPDATE `members` SET created_at = datecreated WHERE datecreated IS NOT NULL AND created_at IS NULL");
            DB::statement("UPDATE `members` SET updated_at = datecreated WHERE datecreated IS NOT NULL AND updated_at IS NULL");
            
            // Set status based on existing verified field for backward compatibility
            DB::statement("UPDATE `members` SET status = 'approved' WHERE verified = 1 AND status = 'pending'");
            DB::statement("UPDATE `members` SET status = 'pending' WHERE verified = 0 AND soft_delete = 0 AND status = 'pending'");
            DB::statement("UPDATE `members` SET status = 'suspended' WHERE soft_delete = 1");
            
            // Set default timestamps for records without any timestamp data
            DB::statement("UPDATE `members` SET created_at = NOW(), updated_at = NOW() WHERE created_at IS NULL AND updated_at IS NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
