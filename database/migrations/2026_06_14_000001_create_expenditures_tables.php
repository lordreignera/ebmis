<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expenditure_rollouts')) {
            Schema::create('expenditure_rollouts', function (Blueprint $table) {
                $table->id();
                $table->string('rollout_number', 40)->unique();
                $table->string('title');
                $table->date('period_start');
                $table->date('period_end');
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('expense_account_id')->index();
                $table->unsignedBigInteger('payment_account_id')->nullable()->index();
                $table->string('status', 30)->default('draft')->index();
                $table->json('basis')->nullable();
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->unsignedBigInteger('generated_by')->nullable()->index();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->unsignedBigInteger('paid_by')->nullable()->index();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('expenditures')) {
            Schema::create('expenditures', function (Blueprint $table) {
                $table->id();
                $table->string('expense_number', 40)->unique();
                $table->string('type', 40)->default('operational')->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('expense_account_id')->index();
                $table->unsignedBigInteger('payment_account_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('requested_by')->nullable()->index();
                $table->unsignedBigInteger('assigned_user_id')->nullable()->index();
                $table->decimal('amount', 15, 2);
                $table->date('expense_date')->index();
                $table->date('due_date')->nullable()->index();
                $table->string('status', 30)->default('pending')->index();
                $table->string('payment_method', 60)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('paid_by')->nullable()->index();
                $table->unsignedBigInteger('journal_entry_id')->nullable()->index();
                $table->unsignedBigInteger('rollout_batch_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->string('receipt_path')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('expenditure_rollout_items')) {
            Schema::create('expenditure_rollout_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rollout_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedInteger('assigned_loans_count')->default(0);
                $table->unsignedInteger('performing_loans_count')->default(0);
                $table->unsignedInteger('overdue_loans_count')->default(0);
                $table->unsignedInteger('followups_count')->default(0);
                $table->decimal('collections_amount', 15, 2)->default(0);
                $table->decimal('payout_amount', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('expenditure_id')->nullable()->index();
                $table->timestamps();
            });
        }

        $this->seedExpenditurePermission();
    }

    public function down(): void
    {
        Schema::dropIfExists('expenditure_rollout_items');
        Schema::dropIfExists('expenditures');
        Schema::dropIfExists('expenditure_rollouts');
    }

    private function seedExpenditurePermission(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        if (!Schema::hasColumn('permissions', 'name')) {
            return;
        }

        DB::table('permissions')->updateOrInsert(
            ['name' => 'manage-expenditures', 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $permissionId = DB::table('permissions')
            ->where('name', 'manage-expenditures')
            ->where('guard_name', 'web')
            ->value('id');

        if (!$permissionId) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn('name', ['Super Administrator', 'superadmin', 'Branch Manager', 'Administrator', 'admin'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }
};
