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
        // Clear cache to avoid conflicts
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ===============================================================
        // 1. SUPER ADMINISTRATOR ROLE
        // ===============================================================
        $superAdmin = Role::firstOrCreate(['name' => 'Super Administrator']);
        
        // Super Admin gets ALL permissions
        if ($superAdmin->permissions->count() === 0) {
            $superAdmin->givePermissionTo(Permission::all());
        }

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
        
        if ($branchManager->permissions->count() === 0) {
            $branchPermissions = Permission::where('name', 'like', '%branch%')
                ->orWhere('name', 'like', '%client%')
                ->orWhere('name', 'like', '%loan%')
                ->orWhere('name', 'like', '%savings%')
                ->orWhere('name', 'like', '%group%')
                ->orWhere('name', 'like', '%repayment%')
                ->get();
            $branchManager->givePermissionTo($branchPermissions);
        }

        // ===============================================================
        // 4. Other Roles (Basic setup)
        // ===============================================================
        Role::firstOrCreate(['name' => 'School Teacher']);
        Role::firstOrCreate(['name' => 'School Accountant']);
        Role::firstOrCreate(['name' => 'Regional HR']);
        Role::firstOrCreate(['name' => 'Loan Officer']);
        Role::firstOrCreate(['name' => 'Cashier']);

        // ===============================================================
        // 5. UPDATE EXISTING SUPER ADMIN USER
        // ===============================================================
        $superAdminUser = User::where('email', 'superadmin@ebims.com')->first();
        
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
            $this->command->error('❌ Super Admin user not found! Expected: superadmin@ebims.com');
        }

        $this->command->info('✅ All roles created successfully!');
        $this->command->info('🔑 Login: superadmin@ebims.com / superadmin123');
    }
}
