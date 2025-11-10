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
        Schema::table('loan_schedules', function (Blueprint $table) {
            // Drop the existing foreign key constraint if it exists
            try {
                $table->dropForeign(['loan_id']);
            } catch (Exception $e) {
                // Foreign key might not exist, continue
            }
            
            // Add the correct foreign key to personal_loans table
            $table->foreign('loan_id')->references('id')->on('personal_loans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_schedules', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['loan_id']);
            
            // Add back the original foreign key (even though it was incorrect)
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
        });
    }
};
