<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_remuneration_policies', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('policy_code', 80);
            $table->string('version', 40);
            $table->string('title');
            $table->string('status', 20)->default('draft');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            // Rates use percentage points: 12 means 12%, not 0.12%.
            $table->decimal('base_current_loan_threshold', 6, 3)->default(90);
            $table->decimal('base_rate_above_threshold', 6, 3)->default(12);
            $table->decimal('base_rate_at_or_below_threshold', 6, 3)->default(5);
            $table->decimal('budget_allocation_rate', 6, 3)->default(10);
            $table->unsignedInteger('par_overdue_days')->default(120);
            $table->decimal('par_bonus_maximum_rate', 6, 3)->default(3);
            $table->unsignedTinyInteger('rounding_scale')->default(2);
            $table->string('timezone', 64)->default('Africa/Kampala');
            // Bands and conversion settings belong to this version of the policy.
            $table->json('configuration');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['policy_code', 'version']);
            $table->index(['status', 'effective_from'], 'staff_rem_policy_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_remuneration_policies');
    }
};
