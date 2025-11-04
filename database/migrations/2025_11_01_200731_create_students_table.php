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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            
            // Personal Information
            $table->string('student_id')->unique(); // Auto-generated unique ID
            $table->string('first_name');
            $table->string('last_name');
            $table->string('other_names')->nullable();
            $table->enum('gender', ['Male', 'Female']);
            $table->date('date_of_birth');
            $table->string('place_of_birth')->nullable();
            $table->string('nationality')->default('Ugandan');
            $table->string('religion')->nullable();
            
            // Contact Information
            $table->text('address')->nullable();
            $table->string('district')->nullable();
            $table->string('village')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            
            // Parent/Guardian Information
            $table->string('parent_name');
            $table->string('parent_phone');
            $table->string('parent_email')->nullable();
            $table->string('parent_occupation')->nullable();
            $table->text('parent_address')->nullable();
            $table->string('relationship')->default('Parent'); // Parent, Guardian, etc.
            
            // Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            
            // Academic Information
            $table->string('admission_number')->nullable();
            $table->date('admission_date')->nullable();
            $table->string('previous_school')->nullable();
            $table->string('academic_year')->nullable();
            $table->enum('boarding_status', ['Day', 'Boarding'])->default('Day');
            
            // Medical Information
            $table->string('blood_group')->nullable();
            $table->text('allergies')->nullable();
            $table->text('medical_conditions')->nullable();
            
            // Photo
            $table->string('photo_path')->nullable();
            
            // Status
            $table->enum('status', ['active', 'suspended', 'transferred', 'graduated', 'expelled'])->default('active');
            
            $table->timestamps();
            $table->softDeletes(); // For archive purposes
            
            // Indexes
            $table->index('school_id');
            $table->index('class_id');
            $table->index('student_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
