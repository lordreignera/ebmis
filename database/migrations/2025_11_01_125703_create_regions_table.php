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
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('region_name', 100)->unique(); // Eastern, Northern, Western, Central
            $table->string('region_code', 10)->unique(); // E, N, W, C
            $table->text('description')->nullable();
            $table->foreignId('hr_id')->nullable()->constrained('users')->onDelete('set null'); // Regional HR
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
