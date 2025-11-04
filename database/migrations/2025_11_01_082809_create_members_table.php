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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->nullable()->index();
            $table->string('fname', 80);
            $table->string('lname', 80);
            $table->string('mname', 191)->nullable();
            $table->string('nin', 80);
            $table->string('contact', 80);
            $table->string('alt_contact', 191)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('plot_no', 191)->nullable();
            $table->string('village', 191)->nullable();
            $table->string('parish', 191)->nullable();
            $table->string('subcounty', 191)->nullable();
            $table->string('county', 191)->nullable();
            $table->unsignedBigInteger('country_id');
            $table->string('gender', 20)->nullable();
            $table->string('dob', 20)->nullable();
            $table->string('fixed_line', 191)->nullable();
            $table->boolean('verified')->default(false);
            $table->string('comments', 150)->nullable();
            $table->integer('member_type');
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->string('pp_file', 1000)->nullable();
            $table->string('id_file', 1000)->nullable();
            $table->boolean('soft_delete')->default(false);
            $table->unsignedBigInteger('del_user')->nullable();
            $table->string('del_comments', 100)->nullable();
            $table->unsignedBigInteger('added_by');
            $table->string('password', 100)->nullable();
            $table->timestamps();
            
            $table->foreign('country_id')->references('id')->on('countries');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
