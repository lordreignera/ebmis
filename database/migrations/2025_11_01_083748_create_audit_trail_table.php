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
        Schema::create('audit_trail', function (Blueprint $table) {
            $table->id();
            $table->string('action', 70);
            $table->string('ip_address', 60);
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('userid');
            $table->text('change_vals')->nullable();
            $table->timestamps();
            
            $table->foreign('member_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('set null');
            $table->foreign('userid')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trail');
    }
};
