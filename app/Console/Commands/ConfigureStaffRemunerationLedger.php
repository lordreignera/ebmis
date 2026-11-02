<?php

namespace App\Console\Commands;

use App\Models\StaffRemunerationPolicy;
use App\Models\SystemAccount;
use App\Models\User;
use App\Services\StaffRemuneration\WeeklyRemunerationWorkflowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ConfigureStaffRemunerationLedger extends Command
{
    protected $signature = 'remuneration:configure-ledger {--policy=2026-10-26-client-clarified} {--source=82} {--actor= : Authorized administrator credited with account setup only} {--apply : Create the authorized zero-balance accounts and map the draft; never approve or activate it}';

    protected $description = 'Prepare a remuneration draft ledger safely, without posting journals or activating payroll';

    public function handle(): int
    {
        $names = ['management' => 'Management Budget', 'revolving' => 'Revolving Fund',
            'growth' => 'Growth Revolving Fund', 'operations_reserve' => 'Internal Operations Reserve',
            'unallocated' => 'Unallocated Bonus'];
        $policy = StaffRemunerationPolicy::where('policy_code', 'weekly_staff_remuneration')->where('version', $this->option('policy'))->firstOrFail();
        $source = SystemAccount::active()->findOrFail((int) $this->option('source'));
        if ($policy->status !== 'draft' || $policy->calculations()->exists()) {
            $this->error('Only an unused draft may be configured. Create a new version for referenced or approved rules.');

            return self::FAILURE;
        }
        if ($source->category !== 'Equity' || $source->is_cash_bank || ! ctype_digit((string) $source->code)) {
            $this->error('Select the authorized active, non-cash Equity source with a numeric chart code.');

            return self::FAILURE;
        }
        $this->info('Policy '.$policy->version.'; source '.$source->full_name.'. Policy approval and activation remain manual.');
        $this->table(['Key', 'Holding account'], collect($names)->map(fn ($name, $key) => [$key, $name])->values());
        if (! $this->option('apply')) {
            $this->comment('Read-only preview. Use --apply only after the account choices have been authorized.');

            return self::SUCCESS;
        }
        $creator = User::find((int) $this->option('actor'));
        if (! $creator || ! $creator->canManageStaffPaymentRollout() || $creator->status !== 'active') {
            $this->error('Provide --actor with the explicitly authorized active administrator for account creation. This is not policy approval.');

            return self::FAILURE;
        }
        $backup = 'remuneration-backups/before-ledger-setup-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
        Storage::disk('local')->put($backup, json_encode([
            'database' => DB::connection()->getDatabaseName(), 'at' => now()->toIso8601String(),
            'policy' => $policy->toArray(), 'system_accounts' => SystemAccount::orderBy('Id')->get()->toArray(),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        DB::transaction(function () use ($policy, $source, $names, $creator): void {
            $policy = StaffRemunerationPolicy::whereKey($policy->id)->lockForUpdate()->firstOrFail();
            $source = SystemAccount::whereKey($source->Id)->lockForUpdate()->firstOrFail();
            if ($policy->status !== 'draft' || $policy->calculations()->exists()) {
                throw new RuntimeException('The draft is no longer editable.');
            }
            $accounts = ['source' => $source->Id];
            foreach ($names as $key => $name) {
                $account = SystemAccount::where('name', $name)->where('parent_account', $source->Id)->first();
                if (! $account) {
                    $subCode = (int) $source->code + 10;
                    while (SystemAccount::where('sub_code', (string) $subCode)->orWhere('code', (string) $subCode)->exists()) {
                        $subCode += 10;
                    }
                    $account = SystemAccount::create([
                        'code' => $source->code, 'sub_code' => (string) $subCode, 'name' => $name,
                        'category' => 'Equity', 'currency' => $source->currency ?: 'UGX',
                        'parent_account' => $source->Id, 'running_balance' => 0, 'status' => 1,
                        'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false,
                        'allow_manual_posting' => false,
                        'added_by' => $creator->id,
                        'description' => 'Officer-tagged remuneration allocation holding; client-authorized ledger setup. No opening balance posted.',
                    ]);
                }
                $accounts[$key] = $account->Id;
            }
            app(WeeklyRemunerationWorkflowService::class)->assertAllocationAccounts($accounts);
            $existing = data_get($policy->configuration, 'fund_allocation.accounts', []);
            if ($existing && $existing != $accounts) {
                throw new RuntimeException('This draft already has different account mappings; no mappings were overwritten.');
            }
            if ($existing != $accounts) {
                $configuration = $policy->configuration;
                $configuration['fund_allocation']['accounts'] = $accounts;
                $configuration['ledger_setup'] = ['at' => now()->toIso8601String(), 'method' => 'authorized_cli_setup', 'account_creator_id' => $creator->id, 'approval_and_activation' => 'administrator_in_setup_screen'];
                $policy->update(['configuration' => $configuration]);
            }
        });
        $this->info('Accounts mapped. No balances, journals, approvals, payments or policy activation were created. Backup: '.$backup);

        return self::SUCCESS;
    }
}
