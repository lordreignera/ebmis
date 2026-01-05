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
        Schema::table('members', function (Blueprint $table) {
            $table->string('cash_security_account_number', 20)->nullable()->unique()->after('mobile_pin');
            $table->string('savings_account_number', 20)->nullable()->unique()->after('cash_security_account_number');
            
            $table->index('cash_security_account_number');
            $table->index('savings_account_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['cash_security_account_number']);
            $table->dropIndex(['savings_account_number']);
            $table->dropColumn(['cash_security_account_number', 'savings_account_number']);
        });
    }
};
