<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loan_collateral_documents')) {
            $this->repairPartiallyCreatedLegacyTable();
            return;
        }

        Schema::create('loan_collateral_documents', function (Blueprint $table) {
            $table->id();
            $table->string('loan_type', 20)->default('personal');
            // Imported EBIMS databases use signed INT primary keys for members and loans.
            $table->integer('loan_id');
            $table->integer('member_id')->nullable();
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

    private function repairPartiallyCreatedLegacyTable(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `loan_collateral_documents` MODIFY `loan_id` INT NOT NULL');
        DB::statement('ALTER TABLE `loan_collateral_documents` MODIFY `member_id` INT NULL');

        if (!$this->foreignKeyExists('loan_collateral_documents_member_id_foreign')) {
            DB::statement(
                'ALTER TABLE `loan_collateral_documents` '
                . 'ADD CONSTRAINT `loan_collateral_documents_member_id_foreign` '
                . 'FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL'
            );
        }

        if (!$this->foreignKeyExists('loan_collateral_documents_uploaded_by_foreign')) {
            DB::statement(
                'ALTER TABLE `loan_collateral_documents` '
                . 'ADD CONSTRAINT `loan_collateral_documents_uploaded_by_foreign` '
                . 'FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL'
            );
        }
    }

    private function foreignKeyExists(string $constraint): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'loan_collateral_documents')
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
