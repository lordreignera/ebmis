<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if permissions table has Spatie structure (name column)
        if (!\Illuminate\Support\Facades\Schema::hasColumn('permissions', 'name')) {
            $this->command->warn('⚠️  Old permissions table structure detected. Skipping role permissions setup.');
            $this->command->info('💡 Run migrations to create Spatie permissions tables, or manually set up roles.');
            return;
        }

        // Clear cache to avoid conflicts
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ===============================================================
        // 1. SUPER ADMINISTRATOR ROLE
        // ===============================================================
        $superAdmin = Role::firstOrCreate(['name' => 'Super Administrator']);
        
        // Super Admin gets ALL permissions
        $superAdmin->syncPermissions(Permission::all());
        Role::where('name', 'superadmin')->get()->each(function (Role $role) {
            $role->syncPermissions(Permission::all());
        });

        // ===============================================================
        // 2. SCHOOL ADMINISTRATOR ROLE
        // ===============================================================
        $schoolAdmin = Role::firstOrCreate(['name' => 'School Administrator']);
        
        if ($schoolAdmin->permissions->count() === 0) {
            $schoolPermissions = Permission::where('name', 'like', '%school%')
                ->orWhere('name', 'like', '%student%')
                ->orWhere('name', 'like', '%teacher%')
                ->orWhere('name', 'like', '%fee%')
                ->orWhere('name', 'like', '%class%')
                ->orWhere('name', 'like', '%subject%')
                ->orWhere('name', 'like', '%attendance%')
                ->orWhere('name', 'like', '%marks%')
                ->get();
            $schoolAdmin->givePermissionTo($schoolPermissions);
        }

        // ===============================================================
        // 3. BRANCH MANAGER ROLE
        // ===============================================================
        $branchManager = Role::firstOrCreate(['name' => 'Branch Manager']);
        
        if ($branchManager->wasRecentlyCreated || $branchManager->permissions->count() === 0) {
            $branchManager->syncPermissions(config('ebmis_permissions.default_roles.Branch Manager', []));
        }

        // ===============================================================
        // 4. Other Roles (Basic setup)
        // ===============================================================
        Role::firstOrCreate(['name' => 'School Teacher']);
        Role::firstOrCreate(['name' => 'School Accountant']);
        Role::firstOrCreate(['name' => 'Regional HR']);
        foreach (['Loan Officer', 'Field Officer'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            if ($role->wasRecentlyCreated || $role->permissions->count() === 0) {
                $role->syncPermissions(config("ebmis_permissions.default_roles.{$roleName}", []));
            }
        }
        Role::firstOrCreate(['name' => 'Cashier']);

        // ===============================================================
        // 5. UPDATE EXISTING SUPER ADMIN USER (IF EXISTS)
        // ===============================================================
        // Check if email column exists (new Laravel schema) or username column (old database)
        $superAdminUser = null;
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'email')) {
                $superAdminUser = User::where('email', 'superadmin@ebims.com')->first();
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn('users', 'username')) {
                $superAdminUser = User::where('username', 'superadmin')->first();
            }
        } catch (\Exception $e) {
            $this->command->warn('⚠️  Could not check for super admin user: ' . $e->getMessage());
        }
        
        if ($superAdminUser) {
            // Update user with multi-tenant fields
            $superAdminUser->update([
                'user_type' => 'super_admin',
                'status' => 'active',
                'approved_at' => now(),
            ]);
            
            // Assign Super Administrator role
            if (!$superAdminUser->hasRole('Super Administrator')) {
                $superAdminUser->assignRole('Super Administrator');
            }
            
            $this->command->info('✅ Super Admin updated with role and permissions');
        } else {
            $this->command->warn('⚠️  Super Admin user not yet created. Will be created by SuperAdminSeeder.');
        }

        $this->command->info('✅ All roles created successfully!');
    }
}
