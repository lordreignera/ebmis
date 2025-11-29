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
        Schema::create('member_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('document_type', 50); // 'id_card', 'passport', 'bank_statement', 'payslip', 'utility_bill', 'other'
            $table->string('document_name', 255);
            $table->string('file_path', 1000);
            $table->string('file_type', 50)->nullable(); // mime type
            $table->integer('file_size')->nullable(); // in bytes
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();
            
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_documents');
    }
};
