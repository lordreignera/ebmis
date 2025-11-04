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
        Schema::create('raw_payments', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 100);
            $table->string('amount', 100);
            $table->text('trans_id')->nullable();
            $table->string('ref', 100)->nullable();
            $table->text('message');
            $table->string('status', 100);
            $table->string('pay_status', 100)->nullable();
            $table->text('pay_message')->nullable();
            $table->string('pay_date', 100)->nullable();
            $table->string('type', 100);
            $table->string('added_by', 10);
            $table->string('direction', 100);
            $table->text('raw_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_payments');
    }
};
