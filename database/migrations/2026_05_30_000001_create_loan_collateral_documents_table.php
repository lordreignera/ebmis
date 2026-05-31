<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_collateral_documents', function (Blueprint $table) {
            $table->id();
            $table->string('loan_type', 20)->default('personal');
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('collateral_field', 80)->nullable();
            $table->string('document_name', 255);
            $table->string('document_type', 80)->nullable();
            $table->string('file_path', 1000);
            $table->string('file_type', 80)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->index(['loan_type', 'loan_id']);
            $table->index('member_id');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_collateral_documents');
    }
};
