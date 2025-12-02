<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CorrectMemberTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'members:correct-types {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Correct member_type logic: ALL individual clients should be type=1 regardless of group membership';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== CORRECT MEMBER_TYPE LOGIC ===');
        $this->newLine();

        $this->warn('CORRECT BUSINESS LOGIC:');
        $this->line('- member_type=1: Individual person/client (whether in a group or not)');
        $this->line('- member_type=2: Group entity (the group itself, not individual members)');
        $this->line('- A client is FIRST an individual before being attached to a group');
        $this->line('- Individual clients (member_type=1) can have a group_id');
        $this->newLine();

        $this->info('SOLUTION:');
        $this->line('Set ALL individual clients to member_type=1, regardless of group_id');
        $this->line('Only actual GROUP entities (organizations) should be member_type=2');
        $this->newLine();

        // Show current state
        $type1_count = Member::where('member_type', 1)->count();
        $type1_with_group = Member::where('member_type', 1)
            ->whereNotNull('group_id')
            ->where('group_id', '>', 0)
            ->count();
            
        $type1_without_group = Member::where('member_type', 1)
            ->where(function($q) {
                $q->whereNull('group_id')->orWhere('group_id', 0);
            })
            ->count();
            
        $type2_count = Member::where('member_type', 2)->count();
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
        $this->line("- Total member_type=1: {$type1_count}");
        $this->line("  * WITH group: {$type1_with_group} ✓ (correct)");
        $this->line("  * WITHOUT group: {$type1_without_group} ✓ (correct)");
        $this->line("- Total member_type=2: {$type2_count}");
        $this->line("  * WITH group: {$type2_with_group} ✗ (should be type=1)");
        $this->line("  * WITHOUT group: {$type2_without_group} ✗ (should be type=1)");
        $this->newLine();

        $total_to_fix = $type2_with_group + $type2_without_group;

        if ($total_to_fix === 0) {
            $this->info('✓ All member types are already correct! No changes needed.');
            return 0;
        }

        $this->warn('WILL UPDATE:');
        $this->line("- {$total_to_fix} members currently set as type=2 → type=1 (Individual)");
        $this->newLine();
        $this->comment('Note: If there are actual GROUP entities (organizations) incorrectly');
        $this->comment('set as member_type=2, please update them manually after this command.');
        $this->newLine();

        // Confirm unless --force flag is used
        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to proceed with these changes?', true)) {
                $this->error('Operation cancelled.');
                return 1;
            }
        }

        DB::beginTransaction();

        try {
            // Set ALL members to type=1 (Individual) since all clients are individuals
            // regardless of group membership status
            $updated = DB::table('members')
                ->where('member_type', 2)
                ->update(['member_type' => 1]);
            
            $this->info("✓ Updated {$updated} members to Individual (type=1)");
            
            DB::commit();
            
            $this->newLine();
            $this->info('✓ SUCCESS!');
            $this->newLine();
            
            // Show new state
            $new_type1_count = Member::where('member_type', 1)->count();
            $new_type1_with_group = Member::where('member_type', 1)
                ->whereNotNull('group_id')
                ->where('group_id', '>', 0)
                ->count();
                
            $new_type1_without_group = Member::where('member_type', 1)
                ->where(function($q) {
                    $q->whereNull('group_id')->orWhere('group_id', 0);
                })
                ->count();
                
            $new_type2_count = Member::where('member_type', 2)->count();
            
            $this->info('NEW STATE:');
            $this->line("- Total member_type=1: {$new_type1_count}");
            $this->line("  * WITH group: {$new_type1_with_group} ✓");
            $this->line("  * WITHOUT group: {$new_type1_without_group} ✓");
            $this->line("- Total member_type=2: {$new_type2_count} (should be 0 unless actual group entities)");
            $this->newLine();
            
            $this->info('All individual clients can now be seen in the Individual Members list,');
            $this->info('regardless of whether they are attached to a group or not.');
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('✗ ERROR: ' . $e->getMessage());
            return 1;
        }
    }
}
