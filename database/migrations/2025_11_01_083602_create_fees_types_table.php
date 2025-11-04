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
        Schema::create('fees_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->integer('account');
            $table->unsignedBigInteger('added_by');
            $table->boolean('isactive')->default(true);
            $table->integer('required_disbursement')->default(0)->nullable();
            $table->timestamps();
            
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees_types');
    }
};
