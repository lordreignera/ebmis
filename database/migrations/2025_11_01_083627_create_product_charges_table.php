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
        Schema::create('product_charges', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->string('name', 70);
            $table->integer('type');
            $table->string('value', 10);
            $table->integer('added_by');
            $table->timestamp('datecreated')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('isactive')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_charges');
    }
};
