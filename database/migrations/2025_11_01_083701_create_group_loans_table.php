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
        Schema::create('group_loans', function (Blueprint $table) {
            $table->id();
            $table->integer('group_id');
            $table->integer('product_type');
            $table->string('code', 50);
            $table->string('interest', 10);
            $table->string('period', 10);
            $table->string('principal', 20)->nullable();
            $table->integer('status');
            $table->integer('verified');
            $table->integer('added_by');
            $table->timestamp('datecreated')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('branch_id');
            $table->text('comments')->nullable();
            $table->integer('charge_type')->default(1)->nullable();
            $table->datetime('date_closed')->nullable();
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
