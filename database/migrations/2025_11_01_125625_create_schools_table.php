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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            
            // Basic School Information
            $table->string('school_name', 255);
            $table->string('school_code', 50)->unique()->nullable();
            $table->string('registration_number', 100)->nullable();
            $table->enum('school_type', ['Primary', 'Secondary', 'Primary & Secondary', 'Nursery', 'University', 'College', 'Other'])->default('Primary');
            $table->enum('ownership', ['Government', 'Private', 'Religious', 'Community', 'NGO'])->default('Private');
            
            // Contact Information
            $table->string('contact_person', 255);
            $table->string('contact_position', 100)->nullable();
            $table->string('email', 191)->unique(); // Reduced length for MySQL compatibility
            $table->string('phone', 50);
            $table->string('alternative_phone', 50)->nullable();
            $table->string('website', 255)->nullable();
            
            // Admin Account Credentials (for school administrator)
            $table->string('admin_password'); // Password set during registration
            $table->timestamp('password_set_at')->nullable(); // When password was set
            
            // Address Information
            $table->text('physical_address');
            $table->string('district', 100);
            $table->string('county', 100)->nullable();
            $table->string('sub_county', 100)->nullable();
            $table->string('parish', 100)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('postal_address', 255)->nullable();
            $table->string('postal_code', 20)->nullable();
            
            // School Details
            $table->year('year_established')->nullable();
            $table->integer('total_students')->default(0);
            $table->integer('total_teachers')->default(0);
            $table->integer('total_non_teaching_staff')->default(0);
            $table->text('facilities_available')->nullable(); // Classrooms, Library, Laboratory, etc.
            $table->enum('medium_of_instruction', ['English', 'Local Language', 'Both'])->default('English');
            
            // Financial Information
            $table->decimal('annual_fees_primary', 10, 2)->nullable();
            $table->decimal('annual_fees_secondary', 10, 2)->nullable();
            $table->decimal('monthly_operational_cost', 15, 2)->nullable();
            $table->enum('banking_with', ['Bank', 'SACCO', 'Microfinance', 'None', 'Other'])->nullable();
            $table->string('current_bank_name', 255)->nullable();
            
            // Legal & Compliance
            $table->string('license_number', 100)->nullable();
            $table->date('license_expiry_date')->nullable();
            $table->string('tax_identification_number', 100)->nullable();
            $table->boolean('has_insurance')->default(false);
            $table->string('insurance_provider', 255)->nullable();
            
            // System Status
            $table->enum('status', ['pending', 'approved', 'suspended', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Additional Information
            $table->text('additional_services')->nullable(); // Transport, Boarding, etc.
            $table->json('documents_submitted')->nullable(); // JSON array of document paths
            $table->text('special_needs_facilities')->nullable();
            $table->boolean('accepts_scholarship_students')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'district']);
            $table->index(['school_type', 'ownership']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
