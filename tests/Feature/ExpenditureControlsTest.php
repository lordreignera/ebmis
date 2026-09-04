<?php

namespace Tests\Feature;

use App\Models\Expenditure;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\Country;
use App\Models\SystemAccount;
use App\Models\User;
use App\Services\MobileMoneyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ExpenditureControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_cannot_approve_their_own_expenditure(): void
    {
        $requester = User::factory()->create(['user_type' => 'super_admin', 'status' => 'active']);
        $expense = Expenditure::create([
            'expense_number' => 'EXP-TEST-001',
            'type' => 'operational',
            'title' => 'Office expense',
            'expense_account_id' => 1,
            'requested_by' => $requester->id,
            'amount' => 50000,
            'expense_date' => now()->toDateString(),
            'status' => Expenditure::STATUS_PENDING,
        ]);

        $this->actingAs($requester)
            ->post(route('admin.expenditures.approve', $expense))
            ->assertSessionHas('error', 'Maker-checker control: the requester cannot approve this expenditure.');

        $this->assertSame(Expenditure::STATUS_PENDING, $expense->fresh()->status);
    }

    public function test_mobile_money_exception_marks_expenditure_failed_and_releases_investment_funds(): void
    {
        $requester = User::factory()->create(['user_type' => 'branch', 'status' => 'active']);
        $approver = User::factory()->create(['user_type' => 'branch', 'status' => 'active']);
        $payer = User::factory()->create(['user_type' => 'super_admin', 'status' => 'active']);

        $paymentAccount = SystemAccount::create([
            'code' => '10000',
            'sub_code' => '10010',
            'name' => 'Cash Account',
            'category' => 'Asset',
            'is_cash_bank' => true,
            'status' => 1,
        ]);
        $country = Country::create([
            'name' => 'Uganda',
            'code' => 'UG',
            'is_active' => true,
        ]);
        $investor = Investor::create([
            'title' => 1,
            'fname' => 'Operating',
            'lname' => 'Fund',
            'address' => 'Kampala',
            'city' => 'Kampala',
            'country' => $country->id,
            'email' => 'fund@example.test',
            'passcode' => 1234,
            'phone' => '0771234567',
            'gender' => 'N/A',
            'IDtype' => 'NIN',
            'IDnumber' => 'CFTEST001',
            'status' => 1,
            'description' => 'Test investor',
            'dob' => '1990-01-01',
            'soft_delete' => 0,
        ]);
        $investment = Investment::create([
            'userid' => $investor->id,
            'type' => 1,
            'name' => 'Operating Fund',
            'amount' => 250000,
            'period' => 1,
            'percentage' => 0,
            'start' => now()->toDateString(),
            'end' => now()->addMonth()->toDateString(),
            'interest' => 0,
            'status' => 1,
        ]);
        $expense = Expenditure::create([
            'expense_number' => 'EXP-TEST-002',
            'type' => 'operational',
            'title' => 'Approved expense',
            'expense_account_id' => 1,
            'requested_by' => $requester->id,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'amount' => 100000,
            'expense_date' => now()->toDateString(),
            'status' => Expenditure::STATUS_APPROVED,
        ]);

        $this->mock(MobileMoneyService::class, function ($mock) {
            $mock->shouldReceive('validatePhoneNumber')->once()->andReturn([
                'valid' => true,
                'formatted_phone' => '256771234567',
                'network' => 'MTN',
            ]);
            $mock->shouldReceive('disburse')->once()->andThrow(new RuntimeException('provider down'));
        });

        $this->actingAs($payer)
            ->post(route('admin.expenditures.pay', $expense), [
                'payment_account_id' => $paymentAccount->Id,
                'investment_id' => $investment->id,
                'mobile_money_phone' => '0771234567',
                'mobile_money_network' => 'MTN',
            ])
            ->assertSessionHas('error', 'Disbursement failed: provider down');

        $expense->refresh();
        $this->assertSame(Expenditure::STATUS_PAYMENT_FAILED, $expense->status);
        $this->assertNull($expense->investment_debited_at);
        $this->assertNull($expense->mobile_money_reference);
        $this->assertSame(250000.0, (float) $investment->fresh()->amount);
    }
}
