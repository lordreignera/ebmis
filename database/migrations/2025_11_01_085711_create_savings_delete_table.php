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
        Schema::create('savings_delete', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('value', 15);
            $table->integer('branch_id')->nullable();
            $table->integer('pdt_id')->nullable();
            $table->string('sperson', 70)->nullable();
            $table->string('sdate', 30)->nullable();
            $table->string('description', 100)->nullable();
            $table->string('sdatecreated', 30)->nullable();
            $table->integer('sadded_by')->nullable();
            $table->unsignedBigInteger('del_user');
            $table->string('del_comments', 100);
            $table->timestamps();
            
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('del_user')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_delete');
    }
};
