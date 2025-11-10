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
        // Check if table already exists (for old database imports)
        if (!Schema::hasTable('system_accounts')) {
            Schema::create('system_accounts', function (Blueprint $table) {
                $table->id('Id'); // Using Id (capital I) to match old database
                $table->string('code', 100);
                $table->string('name', 500);
                $table->string('accountType', 100)->nullable();
                $table->string('accountSubType', 100)->nullable();
                $table->string('currency', 10)->default('Ugx');
                $table->text('description')->nullable();
                $table->integer('parent_account')->default(0);
                $table->double('running_balance')->default(0);
                $table->timestamp('date_created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->unsignedBigInteger('added_by');
                $table->integer('status')->default(0);
                
                $table->foreign('added_by')->references('id')->on('users');
                $table->index(['status', 'accountType']);
                $table->index('parent_account');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_accounts');
    }
};
