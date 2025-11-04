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
        Schema::create('accounts_ledger', function (Blueprint $table) {
            $table->id();
            $table->string('debit_account', 100);
            $table->string('credit_account', 100);
            $table->double('debit_amount');
            $table->double('credit_amount');
            $table->string('currency', 100);
            $table->text('narrative');
            $table->integer('status')->default(1);
            $table->string('added_by', 100);
            $table->string('remarks', 500);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts_ledger');
    }
};
