<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Fund;

class FundsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Migrates existing investment accounts to the funds table
     */
    public function run(): void
    {
        // Check if investment table exists and has data
        if (!DB::getSchemaBuilder()->hasTable('investment')) {
            $this->command->warn('Investment table does not exist. Skipping fund seeding.');
            return;
        }

        $investments = DB::table('investment')
            ->orderBy('id')
            ->get();

        if ($investments->isEmpty()) {
            $this->command->warn('No investment accounts found to migrate.');
            return;
        }

        $this->command->info('Migrating ' . $investments->count() . ' investment accounts to funds table...');

        $migrated = 0;
        $skipped = 0;

        foreach ($investments as $investment) {
            // Skip if already exists (by name)
            if (Fund::where('name', $investment->name)->exists()) {
                $skipped++;
                continue;
            }

            // Determine fund type based on investment type
            // Type 1 = Local/Internal, Type 2 = External/Donor
            $fundType = ($investment->type == 1) ? 'Internal' : 'Donor';

            // Parse amount (stored as string)
            $totalAmount = floatval(str_replace(',', '', $investment->amount));

            // Determine status (investment status: 0=inactive, 1=active, 2=completed)
            $isActive = in_array($investment->status, [1, 2]);

            // Generate unique code
            $code = 'INV-' . str_pad($investment->id, 4, '0', STR_PAD_LEFT);

            // Create fund
            Fund::create([
                'code' => $code,
                'name' => $investment->name,
                'type' => $fundType,
                'description' => $investment->details ?? 'Migrated from investment table',
                'donor_name' => ($fundType === 'Donor') ? $investment->name : null,
                'start_date' => $investment->start ?? null,
                'end_date' => $investment->end ?? null,
                'total_amount' => abs($totalAmount), // Store absolute value
                'disbursed_amount' => 0, // Will need to calculate from disbursements
                'available_amount' => abs($totalAmount),
                'is_active' => $isActive,
                'added_by' => !empty($investment->added_by) ? intval($investment->added_by) : 1,
            ]);

            $migrated++;
        }

        $this->command->info("✅ Successfully migrated {$migrated} funds");
        if ($skipped > 0) {
            $this->command->warn("⚠️ Skipped {$skipped} duplicate funds");
        }

        // Update disbursed amounts based on actual disbursements
        $this->updateDisbursedAmounts();
    }

    /**
     * Calculate and update disbursed amounts for each fund
     */
    private function updateDisbursedAmounts(): void
    {
        $this->command->info('Calculating disbursed amounts from disbursement records...');

        // Get all disbursements with their investment IDs
        if (!DB::getSchemaBuilder()->hasTable('disbursements')) {
            $this->command->warn('Disbursements table not found. Skipping amount calculation.');
            return;
        }

        $disbursementTotals = DB::table('disbursements')
            ->select('inv_id', DB::raw('SUM(amount) as total_disbursed'))
            ->where('status', 2) // Only successful disbursements
            ->whereNotNull('inv_id')
            ->groupBy('inv_id')
            ->get();

        foreach ($disbursementTotals as $total) {
            // Find corresponding fund by the original investment ID
            $investment = DB::table('investment')->where('id', $total->inv_id)->first();
            if (!$investment) continue;

            $fund = Fund::where('name', $investment->name)->first();
            if (!$fund) continue;

            // Update disbursed and available amounts
            $fund->disbursed_amount = $total->total_disbursed;
            $fund->available_amount = $fund->total_amount - $total->total_disbursed;
            $fund->save();

            $this->command->info("  • {$fund->name}: Disbursed UGX " . number_format($total->total_disbursed, 2));
        }

        $this->command->info('✅ Disbursed amounts updated');
    }
}
