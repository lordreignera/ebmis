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
        Schema::table('business_address', function (Blueprint $table) {
            $table->string('street', 50)->nullable()->change();
            $table->string('plot_no', 50)->nullable()->change();
            $table->string('house_no', 50)->nullable()->change();
            $table->string('cell', 50)->nullable()->change();
            $table->string('ward', 50)->nullable()->change();
            $table->string('division', 50)->nullable()->change();
            $table->string('district', 50)->nullable()->change();
            $table->string('country', 15)->nullable()->change();
            $table->string('tel_no', 30)->nullable()->change();
            $table->string('mobile_no', 30)->nullable()->change();
            $table->string('fixed_line', 30)->nullable()->change();
            $table->string('email', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_address', function (Blueprint $table) {
            $table->string('street', 50)->nullable(false)->change();
            $table->string('plot_no', 50)->nullable(false)->change();
            $table->string('house_no', 50)->nullable(false)->change();
            $table->string('cell', 50)->nullable(false)->change();
            $table->string('ward', 50)->nullable(false)->change();
            $table->string('division', 50)->nullable(false)->change();
            $table->string('district', 50)->nullable(false)->change();
            $table->string('country', 15)->nullable(false)->change();
            $table->string('tel_no', 30)->nullable(false)->change();
            $table->string('mobile_no', 30)->nullable(false)->change();
            $table->string('fixed_line', 30)->nullable(false)->change();
            $table->string('email', 50)->nullable(false)->change();
        });
    }
};
