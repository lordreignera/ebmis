<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Administrator role (matching middleware check)
        $role = Role::firstOrCreate(['name' => 'Super Administrator']);

        // Create all permissions if not exist
        $permissions = [
            'manage-users',
            'manage-roles',
            'manage-permissions',
            'manage-schools',
            'manage-loans',
            'approve-loans',
            'view-reports',
            'access-control',
            'manage-access-control',
            // Add more as needed
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        $role->syncPermissions(Permission::all());

        // Create superadmin user
        $user = User::firstOrCreate(
            ['email' => 'superadmin@ebims.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('superadmin123'),
                'user_type' => 'super_admin', // Set user type
                'status' => 'active',
            ]
        );
        
        // Ensure role is assigned
        if (!$user->hasRole('Super Administrator')) {
            $user->assignRole('Super Administrator');
        }
        
        // Also create backward compatible 'superadmin' role
        $legacyRole = Role::firstOrCreate(['name' => 'superadmin']);
        $legacyRole->syncPermissions(Permission::all());
        if (!$user->hasRole('superadmin')) {
            $user->assignRole('superadmin');
        }
    }
}
