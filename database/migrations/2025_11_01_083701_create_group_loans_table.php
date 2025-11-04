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
        Schema::create('group_loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('product_type');
            $table->string('code', 50)->unique();
            $table->string('interest', 10);
            $table->string('period', 10);
            $table->decimal('principal', 15, 2)->nullable();
            $table->integer('status');
            $table->boolean('verified');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('added_by');
            $table->text('comments')->nullable();
            $table->integer('charge_type')->default(1)->nullable();
            $table->datetime('date_closed')->nullable();
            $table->timestamps();
            
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('product_type')->references('id')->on('products');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_loans');
    }
};
