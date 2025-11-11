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
        Schema::table('raw_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('loan_id')->nullable()->after('type');
            $table->unsignedBigInteger('disbursement_id')->nullable()->after('loan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_payments', function (Blueprint $table) {
            $table->dropColumn(['loan_id', 'disbursement_id']);
        });
    }
};
