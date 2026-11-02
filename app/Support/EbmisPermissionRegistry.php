<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EbmisPermissionRegistry
{
    public static function catalog(): array
    {
        return config('ebmis_permissions.catalog', []);
    }

    public static function routePermission(?string $routeName): ?string
    {
        if (!$routeName) {
            return null;
        }

        return config('ebmis_permissions.route_permissions', [])[$routeName] ?? null;
    }

    public static function hasExplicitRoutePermission(string $routeName): bool
    {
        return array_key_exists($routeName, config('ebmis_permissions.route_permissions', []));
    }

    public static function isRouteControlled(string $permission): bool
    {
        return in_array($permission, config('ebmis_permissions.route_permissions', []), true);
    }

    /**
     * A user may enter the EBIMS workspace when they have the explicit
     * workspace permission or at least one permission for an operational
     * module. The second condition keeps older roles working when they were
     * saved before the workspace umbrella permission was introduced.
     */
    public static function userCanAccessWorkspace($user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->can('access-ebmis-modules')) {
            return true;
        }

        return collect(config('ebmis_permissions.module_permissions', []))
            ->flatten()
            ->unique()
            ->contains(fn (string $permission): bool => $user->can($permission));
    }

    public static function rolePermissionsWithWorkspace(array $permissionNames): array
    {
        $permissionNames = array_values(array_unique($permissionNames));

        foreach ($permissionNames as $permissionName) {
            if ($permissionName !== 'access-ebmis-modules' && self::isRouteControlled($permissionName)) {
                $permissionNames[] = 'access-ebmis-modules';
                break;
            }
        }

        return array_values(array_unique($permissionNames));
    }

    public static function groupedPermissions(Collection $permissions): Collection
    {
        return $permissions
            ->sortBy('name')
            ->each(function ($permission) {
                $metadata = self::catalog()[$permission->name] ?? [];

                $permission->access_group = $metadata['group'] ?? Str::headline(Str::before($permission->name, '-'));
                $permission->display_name = $metadata['label'] ?? Str::headline($permission->name);
                $permission->description = $metadata['description'] ?? null;
                $permission->is_route_controlled = self::isRouteControlled($permission->name);
            })
            ->groupBy('access_group')
            ->sortKeys();
    }
}
