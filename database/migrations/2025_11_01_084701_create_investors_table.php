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
        Schema::create('investors', function (Blueprint $table) {
            $table->id();
            $table->integer('title');
            $table->string('fname', 200);
            $table->string('lname', 200);
            $table->string('address', 500);
            $table->string('city', 500);
            $table->unsignedBigInteger('country');
            $table->string('zip', 100)->nullable();
            $table->string('email', 500);
            $table->integer('passcode');
            $table->string('phone', 100);
            $table->string('gender', 100);
            $table->string('IDtype', 500);
            $table->string('IDnumber', 500);
            $table->integer('status')->default(0);
            $table->text('description');
            $table->string('photo_path', 500)->nullable();
            $table->string('image_path', 500)->nullable();
            $table->string('dob', 200);
            $table->integer('soft_delete')->default(0);
            $table->integer('del_user')->nullable();
            $table->string('del_comments', 100)->nullable();
            $table->timestamps();
            
            $table->foreign('country')->references('id')->on('countries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investors');
    }
};
