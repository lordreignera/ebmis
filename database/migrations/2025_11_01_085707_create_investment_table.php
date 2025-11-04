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
        Schema::create('investment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userid');
            $table->integer('type');
            $table->string('name', 200);
            $table->string('amount', 15);
            $table->string('period', 10);
            $table->string('percentage', 10);
            $table->string('start', 30);
            $table->string('end', 30);
            $table->string('interest', 10);
            $table->text('details')->nullable();
            $table->integer('status');
            $table->string('added_by', 100)->nullable();
            $table->timestamps();
            
            $table->foreign('userid')->references('id')->on('investors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment');
    }
};
