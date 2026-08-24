<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dashboard_events')) {
            Schema::create('dashboard_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title', 120);
                $table->date('event_date');
                $table->time('event_time')->nullable();
                $table->string('category', 40)->default('general');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['event_date', 'category']);
            });
        }

        $this->syncDashboardEventPermission();
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_events');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function syncDashboardEventPermission(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate('manage-dashboard-events', 'web');
        $roleNames = array_unique(array_merge(
            ['Super Administrator', 'superadmin', 'Administrator', 'administrator', 'admin'],
            array_keys(config('ebmis_permissions.default_roles', []))
        ));

        Role::whereIn('name', $roleNames)
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
