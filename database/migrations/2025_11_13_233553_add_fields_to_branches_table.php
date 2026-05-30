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
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'code')) {
                $table->string('code', 50)->nullable()->after('name');
            }
            if (!Schema::hasColumn('branches', 'phone')) {
                $table->string('phone', 20)->nullable()->after('address');
            }
            if (!Schema::hasColumn('branches', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('branches', 'country')) {
                $table->string('country', 100)->nullable()->after('email');
            }
            if (!Schema::hasColumn('branches', 'description')) {
                $table->text('description')->nullable()->after('country');
            }
            if (!Schema::hasColumn('branches', 'is_active')) {
                $table->boolean('is_active')->default(1)->after('description');
            }
            if (!Schema::hasColumn('branches', 'added_by')) {
                $table->unsignedBigInteger('added_by')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('branches', 'created_at')) {
                $column = $table->timestamp('created_at')->nullable();

                if (Schema::hasColumn('branches', 'date_created')) {
                    $column->after('date_created');
                }
            }
            if (!Schema::hasColumn('branches', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });

        if (Schema::hasColumn('branches', 'date_created') && Schema::hasColumn('branches', 'created_at')) {
            \DB::statement('UPDATE branches SET created_at = date_created WHERE created_at IS NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_values(array_filter([
            'code',
            'country',
            'description',
            'added_by',
        ], function ($column) {
            return Schema::hasColumn('branches', $column);
        }));

        if (!empty($columns)) {
            Schema::table('branches', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
