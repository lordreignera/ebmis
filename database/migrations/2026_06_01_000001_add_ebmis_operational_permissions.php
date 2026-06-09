<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Add the operational permission catalogue and preserve existing access.
     */
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = array_unique(array_merge(
            array_keys(config('ebmis_permissions.catalog', [])),
            array_values(config('ebmis_permissions.route_permissions', [])),
            Arr::flatten(config('ebmis_permissions.default_roles', []))
        ));

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (config('ebmis_permissions.default_roles', []) as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->givePermissionTo($permissions);
        }

        Role::whereIn('name', ['Super Administrator', 'superadmin'])
            ->get()
            ->each(fn (Role $role) => $role->syncPermissions(Permission::all()));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Leave configured permissions in place when rolling back application code.
     */
    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
