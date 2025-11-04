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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('title', 1000);
            $table->string('url', 1000);
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('added_by');
            $table->integer('status')->default(0);
            $table->timestamps();
            
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
