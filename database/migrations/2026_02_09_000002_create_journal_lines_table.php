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
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id('Id');
            
            // Link to journal entry header
            $table->unsignedBigInteger('journal_entry_id');
            $table->integer('line_number')->comment('Sequence within journal entry');
            
            // Account and amounts
            $table->unsignedBigInteger('account_id')->comment('System Account ID');
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            
            // Line-specific details
            $table->string('narrative', 500)->nullable()->comment('Line-specific description');
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('journal_entry_id')->references('Id')->on('journal_entries')->onDelete('cascade');
            $table->foreign('account_id')->references('Id')->on('system_accounts')->onDelete('restrict');
            
            // Indexes
            $table->index(['journal_entry_id', 'line_number']);
            $table->index('account_id');
            
            // Unique constraint: prevent duplicate line numbers in same entry
            $table->unique(['journal_entry_id', 'line_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
