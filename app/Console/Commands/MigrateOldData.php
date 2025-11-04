<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class MigrateOldData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:old-data {--table= : Specific table to migrate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from old CodeIgniter EBIMS database to new Laravel system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration from old EBIMS database...');
        
        $table = $this->option('table');
        
        if ($table) {
            $this->migrateTable($table);
        } else {
            // Migrate all tables in order
            $this->migrateCountries();
            $this->migrateBranches();
            $this->migrateUsers();
            $this->migratePermissions();
            // Add more migrations as needed
        }
        
        $this->info('Migration completed successfully!');
    }

    protected function migrateTable($table)
    {
        switch ($table) {
            case 'countries':
                $this->migrateCountries();
                break;
            case 'branches':
                $this->migrateBranches();
                break;
            case 'users':
                $this->migrateUsers();
                break;
            case 'permissions':
                $this->migratePermissions();
                break;
            default:
                $this->error("Unknown table: {$table}");
        }
    }

    protected function migrateCountries()
    {
        $this->info('Migrating countries...');
        
        $oldCountries = DB
            ->table('countries')
            ->get();
        
        foreach ($oldCountries as $country) {
            DB::table('countries')->updateOrInsert(
                ['id' => $country->id],
                [
                    'id' => $country->id,
                    'name' => $country->name ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        
        $this->info("Migrated {$oldCountries->count()} countries");
    }

    protected function migrateBranches()
    {
        $this->info('Migrating branches...');
        
        $oldBranches = DB
            ->table('branches')
            ->get();
        
        foreach ($oldBranches as $branch) {
            DB::table('branches')->updateOrInsert(
                ['id' => $branch->id],
                [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'address' => $branch->address ?? '',
                    'created_at' => $branch->date_created ?? now(),
                    'updated_at' => now(),
                ]
            );
        }
        
        $this->info("Migrated {$oldBranches->count()} branches");
    }

    protected function migrateUsers()
    {
        $this->info('Migrating users...');
        
        $oldUsers = DB
            ->table('users')
            ->where('isactive', 1)
            ->get();
        
        $migratedCount = 0;
        
        foreach ($oldUsers as $oldUser) {
            // Create or update user
            $user = User::updateOrCreate(
                ['email' => $oldUser->username . '@ebims.com'], // Use username as email if no email exists
                [
                    'name' => trim($oldUser->fname . ' ' . $oldUser->lname),
                    'password' => Hash::make('ChangeMe123!'), // Force password reset on first login
                    'created_at' => $oldUser->datecreated ?? now(),
                    'updated_at' => now(),
                ]
            );
            
            // Store legacy ID mapping
            DB::table('user_legacy_mapping')->updateOrInsert(
                ['new_user_id' => $user->id],
                [
                    'old_user_id' => $oldUser->id,
                    'old_username' => $oldUser->username,
                    'branch_id' => $oldUser->branch_id,
                    'user_type' => $oldUser->utype,
                ]
            );
            
            // Assign role based on user type
            $this->assignUserRole($user, $oldUser->utype);
            
            $migratedCount++;
        }
        
        $this->info("Migrated {$migratedCount} users");
    }

    protected function assignUserRole($user, $userType)
    {
        // Map old user types to new roles
        $roleMapping = [
            1 => 'superadmin',
            2 => 'admin',
            3 => 'branch-manager',
            4 => 'staff',
            // Add more mappings as needed
        ];
        
        $roleName = $roleMapping[$userType] ?? 'staff';
        
        // Create role if it doesn't exist
        $role = Role::firstOrCreate(['name' => $roleName]);
        
        // Assign role to user
        if (!$user->hasRole($roleName)) {
            $user->assignRole($role);
        }
    }

    protected function migratePermissions()
    {
        $this->info('Migrating permissions...');
        
        $oldPermissions = DB
            ->table('permissions')
            ->get();
        
        foreach ($oldPermissions as $oldPerm) {
            Permission::firstOrCreate([
                'name' => $oldPerm->code ?? $oldPerm->permission,
                'guard_name' => 'web',
            ]);
        }
        
        $this->info("Migrated {$oldPermissions->count()} permissions");
    }
}
