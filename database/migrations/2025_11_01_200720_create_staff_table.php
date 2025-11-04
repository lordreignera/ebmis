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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            
            // Personal Information
            $table->string('staff_id')->unique(); // Auto-generated unique ID
            $table->string('first_name');
            $table->string('last_name');
            $table->string('other_names')->nullable();
            $table->enum('gender', ['Male', 'Female']);
            $table->date('date_of_birth');
            $table->string('nationality')->default('Ugandan');
            $table->string('national_id')->nullable();
            $table->string('religion')->nullable();
            
            // Contact Information
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('district')->nullable();
            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_phone')->nullable();
            
            // Employment Information
            $table->enum('staff_type', ['Teaching', 'Non-Teaching'])->default('Teaching');
            $table->string('position'); // e.g., Teacher, Headteacher, Accountant, Cleaner
            $table->string('department')->nullable(); // e.g., Mathematics, Science, Administration
            $table->string('subjects_taught')->nullable(); // For teaching staff
            $table->date('date_joined');
            $table->string('employee_number')->nullable();
            $table->enum('employment_type', ['Full-Time', 'Part-Time', 'Contract'])->default('Full-Time');
            
            // Qualifications
            $table->string('highest_qualification')->nullable(); // e.g., Degree, Diploma, Certificate
            $table->string('institution_attended')->nullable();
            $table->year('year_of_graduation')->nullable();
            $table->text('certifications')->nullable(); // JSON or comma-separated
            
            // Salary Information
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0); // Transport, housing, etc.
            $table->decimal('total_salary', 12, 2)->default(0); // Basic + Allowances
            $table->enum('payment_frequency', ['Monthly', 'Weekly', 'Daily'])->default('Monthly');
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('mobile_money_number')->nullable();
            
            // Documents
            $table->string('cv_path')->nullable();
            $table->string('certificate_path')->nullable();
            $table->string('id_photo_path')->nullable();
            
            // Status
            $table->enum('status', ['active', 'on_leave', 'suspended', 'terminated', 'resigned'])->default('active');
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('school_id');
            $table->index('staff_id');
            $table->index('staff_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
