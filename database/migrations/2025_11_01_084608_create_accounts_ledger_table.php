<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts_ledger', function (Blueprint $table) {
            $table->integer('Id')->autoIncrement()->primary();
            $table->string('debit_account', 100)->charset('utf8mb4')->collation('utf8mb4_general_ci');
            $table->string('credit_account', 100)->charset('utf8mb4')->collation('utf8mb4_general_ci');
            $table->double('debit_amount');
            $table->double('credit_amount');
            $table->string('currency', 100)->charset('utf8mb4')->collation('utf8mb4_general_ci');
            $table->text('narrative')->charset('utf8mb4')->collation('utf8mb4_general_ci');
            $table->timestamp('date_created')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('status')->default(1)->comment('1=active, 2=reversed');
            $table->string('added_by', 100)->charset('utf8mb4')->collation('utf8mb4_general_ci');
            $table->string('remarks', 500)->charset('utf8mb4')->collation('utf8mb4_general_ci');
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
