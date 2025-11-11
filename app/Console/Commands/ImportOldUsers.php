<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class ImportOldUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:import-old 
                            {--dry-run : Run without actually importing}
                            {--skip-existing : Skip users that already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from old ebims database with default password and Branch Manager role';

    protected $imported = 0;
    protected $skipped = 0;
    protected $errors = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting user import from old ebims database...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $skipExisting = $this->option('skip-existing');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No data will be imported');
            $this->newLine();
        }

        // Check if Branch Manager role exists
        $branchManagerRole = Role::where('name', 'Branch Manager')->first();
        $superAdminRole = Role::where('name', 'Super Administrator')->first();

        if (!$branchManagerRole) {
            $this->error('❌ Branch Manager role not found! Run: php artisan db:seed --class=RolesSeeder');
            return 1;
        }

        if (!$superAdminRole) {
            $this->error('❌ Super Administrator role not found! Run: php artisan db:seed --class=RolesSeeder');
            return 1;
        }

        // Get all active users from old database
        try {
            $oldUsers = DB::connection('mysql')->select('
                SELECT id, fname, lname, username, utype, branch_id, isactive, datecreated
                FROM ebims.users
                WHERE isactive = 1
                ORDER BY id
            ');
        } catch (\Exception $e) {
            $this->error('❌ Error connecting to old database: ' . $e->getMessage());
            return 1;
        }

        if (empty($oldUsers)) {
            $this->warn('⚠️  No users found in old database');
            return 0;
        }

        $this->info('Found ' . count($oldUsers) . ' active users in old database');
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($oldUsers));
        $progressBar->start();

        foreach ($oldUsers as $oldUser) {
            $progressBar->advance();

            try {
                $this->importUser($oldUser, $branchManagerRole, $superAdminRole, $dryRun, $skipExisting);
            } catch (\Exception $e) {
                $this->errors++;
                $this->newLine();
                $this->error("❌ Error importing user {$oldUser->username}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('═══════════════════════════════════════════');
        $this->info('           IMPORT SUMMARY');
        $this->info('═══════════════════════════════════════════');
        $this->line("✅ Imported: {$this->imported}");
        $this->line("⏭️  Skipped:  {$this->skipped}");
        $this->line("❌ Errors:   {$this->errors}");
        $this->info('═══════════════════════════════════════════');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  This was a DRY RUN - no data was actually imported');
            $this->info('Run without --dry-run to import users');
        } else {
            $this->info('🎉 Import completed!');
            $this->info('📧 All users can login with password: 123456789');
            $this->warn('⚠️  Users should change their passwords after first login');
        }

        return 0;
    }

    /**
     * Import a single user
     */
    protected function importUser($oldUser, $branchManagerRole, $superAdminRole, $dryRun, $skipExisting)
    {
        // Generate email from username
        $email = strtolower($oldUser->username) . '@ebims.local';
        
        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        
        if ($existingUser) {
            if ($skipExisting) {
                $this->skipped++;
                return;
            } else {
                // Update existing user
                if (!$dryRun) {
                    $existingUser->update([
                        'name' => trim($oldUser->fname . ' ' . $oldUser->lname),
                        'branch_id' => $oldUser->branch_id > 0 ? $oldUser->branch_id : null,
                        'user_type' => $oldUser->utype == 1 ? 'super_admin' : 'branch',
                        'status' => 'active',
                        'approved_at' => $oldUser->datecreated,
                    ]);

                    // Assign role
                    $role = $oldUser->utype == 1 ? $superAdminRole : $branchManagerRole;
                    if (!$existingUser->hasRole($role->name)) {
                        $existingUser->assignRole($role);
                    }
                }
                $this->skipped++;
                return;
            }
        }

        // Create new user
        $userData = [
            'name' => trim($oldUser->fname . ' ' . $oldUser->lname),
            'email' => $email,
            'password' => Hash::make('123456789'),
            'branch_id' => $oldUser->branch_id > 0 ? $oldUser->branch_id : null,
            'user_type' => $oldUser->utype == 1 ? 'super_admin' : 'branch',
            'status' => 'active',
            'email_verified_at' => now(),
            'approved_at' => $oldUser->datecreated,
            'created_at' => $oldUser->datecreated,
        ];

        if (!$dryRun) {
            $newUser = User::create($userData);

            // Assign role based on user type
            // utype: 1 = Super Admin, 2 = Branch Manager, 3 = Other
            if ($oldUser->utype == 1 || strtolower($oldUser->username) == 'admin') {
                $newUser->assignRole($superAdminRole);
            } else {
                $newUser->assignRole($branchManagerRole);
            }
        }

        $this->imported++;
    }
}
