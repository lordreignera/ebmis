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
        Schema::table('fees', function (Blueprint $table) {
            $table->string('payment_phone', 20)->nullable()->after('payment_type')->comment('Actual phone number used for mobile money payment');
            $table->decimal('original_amount', 15, 2)->nullable()->after('amount')->comment('Original amount before any modifications during retry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropColumn(['payment_phone', 'original_amount']);
        });
    }
};
