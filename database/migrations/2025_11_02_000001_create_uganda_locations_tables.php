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
        // Districts table
        Schema::create('uganda_districts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('region')->nullable(); // Central, Eastern, Northern, Western
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        // Subcounties table
        Schema::create('uganda_subcounties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('uganda_districts')->onDelete('cascade');
            $table->string('name');
            $table->string('type')->default('subcounty'); // subcounty, division, municipality, town council
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            
            $table->index('district_id');
        });

        // Parishes table
        Schema::create('uganda_parishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcounty_id')->constrained('uganda_subcounties')->onDelete('cascade');
            $table->string('name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            
            $table->index('subcounty_id');
        });

        // Villages table
        Schema::create('uganda_villages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parish_id')->constrained('uganda_parishes')->onDelete('cascade');
            $table->string('name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            
            $table->index('parish_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uganda_villages');
        Schema::dropIfExists('uganda_parishes');
        Schema::dropIfExists('uganda_subcounties');
        Schema::dropIfExists('uganda_districts');
    }
};
