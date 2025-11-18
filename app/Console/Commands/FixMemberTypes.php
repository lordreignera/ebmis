<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMemberTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'members:fix-types {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix historical member_type data inconsistency (swap Individual/Group values)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== FIX HISTORICAL MEMBER_TYPE DATA ===');
        $this->newLine();

        $this->warn('PROBLEM:');
        $this->line('- Historical data uses: member_type=2 for Individual, member_type=1 for Group');
        $this->line('- member_types table says: ID=1 is Individual, ID=2 is Group');
        $this->line('- This causes inconsistency with new members');
        $this->newLine();

        $this->info('SOLUTION:');
        $this->line('Update ALL old members to use correct member_type values:');
        $this->line('- Members WITHOUT group_id (or group_id=0) → member_type = 1 (Individual)');
        $this->line('- Members WITH valid group_id → member_type = 2 (Group)');
        $this->newLine();

        // Show current state
        $type1_with_group = Member::where('member_type', 1)
            ->whereNotNull('group_id')
            ->where('group_id', '>', 0)
            ->count();
            
        $type1_without_group = Member::where('member_type', 1)
            ->where(function($q) {
                $q->whereNull('group_id')->orWhere('group_id', 0);
            })
            ->count();
            
        $type2_with_group = Member::where('member_type', 2)
            ->whereNotNull('group_id')
            ->where('group_id', '>', 0)
            ->count();
            
        $type2_without_group = Member::where('member_type', 2)
            ->where(function($q) {
                $q->whereNull('group_id')->orWhere('group_id', 0);
            })
            ->count();

        $this->info('CURRENT STATE:');
        $this->line("- member_type=1 WITH group: {$type1_with_group}");
        $this->line("- member_type=1 WITHOUT group: {$type1_without_group}");
        $this->line("- member_type=2 WITH group: {$type2_with_group}");
        $this->line("- member_type=2 WITHOUT group: {$type2_without_group}");
        $this->newLine();

        $this->warn('WILL UPDATE:');
        $this->line("- {$type2_without_group} members (type=2, no group) → type=1 (Individual)");
        $this->line("- {$type1_with_group} members (type=1, has group) → type=2 (Group)");
        $this->newLine();

        // Check if already fixed
        if ($type1_with_group === 0 && $type2_without_group === 0) {
            $this->info('✓ Member types are already correct! No changes needed.');
            return 0;
        }

        // Confirm unless --force flag is used
        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to proceed with these changes?', false)) {
                $this->error('Operation cancelled.');
                return 1;
            }
        }

        DB::beginTransaction();

        try {
            // Fix members WITHOUT group (should be Individual = type 1)
            $updated1 = DB::table('members')
                ->where('member_type', 2)
                ->where(function($q) {
                    $q->whereNull('group_id')->orWhere('group_id', 0);
                })
                ->update(['member_type' => 1]);
            
            $this->info("✓ Updated {$updated1} members without groups to Individual (type=1)");
            
            // Fix members WITH group (should be Group = type 2)
            $updated2 = DB::table('members')
                ->where('member_type', 1)
                ->whereNotNull('group_id')
                ->where('group_id', '>', 0)
                ->update(['member_type' => 2]);
            
            $this->info("✓ Updated {$updated2} members with groups to Group (type=2)");
            
            DB::commit();
            
            $this->newLine();
            $this->info('✓ SUCCESS!');
            $this->newLine();
            
            // Show new state
            $new_type1_with_group = Member::where('member_type', 1)
                ->whereNotNull('group_id')
                ->where('group_id', '>', 0)
                ->count();
                
            $new_type1_without_group = Member::where('member_type', 1)
                ->where(function($q) {
                    $q->whereNull('group_id')->orWhere('group_id', 0);
                })
                ->count();
                
            $new_type2_with_group = Member::where('member_type', 2)
                ->whereNotNull('group_id')
                ->where('group_id', '>', 0)
                ->count();
                
            $new_type2_without_group = Member::where('member_type', 2)
                ->where(function($q) {
                    $q->whereNull('group_id')->orWhere('group_id', 0);
                })
                ->count();
            
            $this->info('NEW STATE:');
            $this->line("- member_type=1 WITH group: {$new_type1_with_group} (should be 0)");
            $this->line("- member_type=1 WITHOUT group: {$new_type1_without_group} (Individual members ✓)");
            $this->line("- member_type=2 WITH group: {$new_type2_with_group} (Group members ✓)");
            $this->line("- member_type=2 WITHOUT group: {$new_type2_without_group} (should be 0)");
            $this->newLine();
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('✗ ERROR: ' . $e->getMessage());
            return 1;
        }
    }
}
