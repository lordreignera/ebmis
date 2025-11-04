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
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('class_name'); // e.g., "Primary 1", "S1 Arts", "Nursery"
            $table->string('class_code')->nullable(); // e.g., "P1A", "S1-ARTS"
            $table->string('level')->nullable(); // e.g., "Primary", "O-Level", "A-Level"
            $table->string('stream')->nullable(); // e.g., "A", "B", "Arts", "Science"
            $table->foreignId('class_teacher_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->integer('capacity')->default(0); // Maximum students
            $table->integer('current_enrollment')->default(0); // Current student count
            $table->text('description')->nullable();
            $table->string('academic_year')->nullable(); // e.g., "2025/2026"
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->timestamps();
            
            // Indexes
            $table->index('school_id');
            $table->index('class_teacher_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_classes');
    }
};
