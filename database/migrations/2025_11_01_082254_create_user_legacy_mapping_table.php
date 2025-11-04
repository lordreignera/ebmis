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
        Schema::create('user_legacy_mapping', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('new_user_id');
            $table->unsignedInteger('old_user_id');
            $table->string('old_username', 191);
            $table->unsignedInteger('branch_id')->nullable();
            $table->integer('user_type')->nullable();
            $table->timestamps();
            
            $table->foreign('new_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('old_user_id');
            $table->index('old_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_legacy_mapping');
    }
};
