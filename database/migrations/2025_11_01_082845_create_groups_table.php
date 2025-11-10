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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80);
            $table->string('name', 100);
            $table->string('inception_date', 20);
            $table->text('address');
            $table->string('sector', 80);
            $table->integer('type')->comment('1-open,2-closed');
            $table->integer('verified')->default(0)->comment('0-no,1-yes');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('added_by');
            $table->timestamp('datecreated')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamps();
            
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
