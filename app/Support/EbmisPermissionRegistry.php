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
