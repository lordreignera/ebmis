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
        Schema::create('system_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100);
            $table->string('name', 500);
            $table->string('accountType', 100)->nullable();
            $table->string('accountSubType', 100)->nullable();
            $table->string('currency', 10);
            $table->text('description')->nullable();
            $table->integer('parent_account')->default(0);
            $table->double('running_balance');
            $table->unsignedBigInteger('added_by');
            $table->integer('status')->default(0);
            $table->timestamp('date_created')->nullable();
            
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_accounts');
    }
};
