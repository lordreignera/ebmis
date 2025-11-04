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
        Schema::create('paypal_orders', function (Blueprint $table) {
            $table->id();
            $table->string('inv_id', 100)->nullable();
            $table->string('ref', 100)->nullable();
            $table->string('myintent', 100)->nullable();
            $table->string('status', 100)->nullable();
            $table->text('dump')->nullable();
            $table->text('dump_cancel')->nullable();
            $table->datetime('date_cancel')->nullable();
            $table->text('dump_approve')->nullable();
            $table->datetime('date_approve')->nullable();
            $table->text('dump_error')->nullable();
            $table->datetime('date_error')->nullable();
            $table->string('order_status', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paypal_orders');
    }
};
