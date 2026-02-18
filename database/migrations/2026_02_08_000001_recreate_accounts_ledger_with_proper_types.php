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
        // Drop the old table and recreate with proper structure
        Schema::dropIfExists('accounts_ledger');
        
        Schema::create('accounts_ledger', function (Blueprint $table) {
            $table->id('Id');
            $table->unsignedBigInteger('debit_account')->comment('FK to system_accounts.Id');
            $table->unsignedBigInteger('credit_account')->comment('FK to system_accounts.Id');
            $table->decimal('debit_amount', 15, 2);
            $table->decimal('credit_amount', 15, 2);
            $table->string('currency', 10)->default('UGX');
            $table->text('narrative');
            $table->timestamp('date_created')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('status')->default(1)->comment('1=active, 2=reversed');
            $table->unsignedBigInteger('added_by')->nullable();
            $table->string('remarks', 500)->nullable();
            
            // Foreign keys
            $table->foreign('debit_account')->references('Id')->on('system_accounts')->onDelete('restrict');
            $table->foreign('credit_account')->references('Id')->on('system_accounts')->onDelete('restrict');
            $table->foreign('added_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index('debit_account');
            $table->index('credit_account');
            $table->index('date_created');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts_ledger');
    }
};
