<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\CashSecurityController;
use App\Models\CashSecurity;
use App\Models\Member;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\MobileMoneyService;
use App\Services\RepaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CashSecurityMobileMoneyCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_callback_confirms_matching_cash_security_and_posts_gl_entry(): void
    {
        $cashSecurity = $this->createPendingCashSecurity('CASH-SECURITY-SUCCESS');

        $mobileMoneyService = Mockery::mock(MobileMoneyService::class);
        $mobileMoneyService->shouldReceive('processCallback')
            ->once()
            ->andReturn([
                'valid' => true,
                'success' => true,
                'transaction_reference' => 'CASH-SECURITY-SUCCESS',
                'status_code' => '00',
                'status_description' => 'Completed',
            ]);

        $accountingService = Mockery::mock(AccountingService::class);
        $accountingService->shouldReceive('postCashSecurityEntry')
            ->once()
            ->withArgs(fn ($security) => $security->is($cashSecurity))
            ->andReturn(null);

        $result = (new RepaymentService($mobileMoneyService, $accountingService))
            ->processPaymentCallback(['transactionReferenceNumber' => 'CASH-SECURITY-SUCCESS']);

        $this->assertTrue($result['success']);
        $this->assertSame($cashSecurity->id, $result['cash_security_id']);
        $this->assertSame(1, $cashSecurity->fresh()->status);
        $this->assertSame('Completed', $cashSecurity->fresh()->payment_status);
    }

    public function test_failed_callback_marks_matching_cash_security_as_failed_without_posting_gl_entry(): void
    {
        $cashSecurity = $this->createPendingCashSecurity('CASH-SECURITY-FAILED');

        $mobileMoneyService = Mockery::mock(MobileMoneyService::class);
        $mobileMoneyService->shouldReceive('processCallback')
            ->once()
            ->andReturn([
                'valid' => true,
                'success' => false,
                'transaction_reference' => 'CASH-SECURITY-FAILED',
                'status_code' => '02',
                'status_description' => 'Declined',
            ]);

        $accountingService = Mockery::mock(AccountingService::class);
        $accountingService->shouldNotReceive('postCashSecurityEntry');

        $result = (new RepaymentService($mobileMoneyService, $accountingService))
            ->processPaymentCallback(['transactionReferenceNumber' => 'CASH-SECURITY-FAILED']);

        $this->assertFalse($result['success']);
        $this->assertSame($cashSecurity->id, $result['cash_security_id']);
        $this->assertSame(2, $cashSecurity->fresh()->status);
        $this->assertSame('Failed', $cashSecurity->fresh()->payment_status);
    }

    public function test_status_polling_keeps_processing_cash_security_pending(): void
    {
        $cashSecurity = $this->createPendingCashSecurity('CASH-SECURITY-PENDING');

        $mobileMoneyService = Mockery::mock(MobileMoneyService::class);
        $mobileMoneyService->shouldReceive('checkTransactionStatus')
            ->once()
            ->with('CASH-SECURITY-PENDING')
            ->andReturn([
                'success' => true,
                'status' => 'pending',
                'status_code' => '01',
                'message' => 'Transaction received and is being processed',
                'transaction_reference' => 'CASH-SECURITY-PENDING',
            ]);

        $this->app->instance(MobileMoneyService::class, $mobileMoneyService);

        $response = (new CashSecurityController())->checkPaymentStatus('CASH-SECURITY-PENDING');

        $this->assertSame('pending', $response->getData(true)['status']);
        $this->assertSame(0, $cashSecurity->fresh()->status);
        $this->assertSame('Pending', $cashSecurity->fresh()->payment_status);
    }

    private function createPendingCashSecurity(string $reference): CashSecurity
    {
        $member = Member::factory()->create();
        $user = User::factory()->create();

        return CashSecurity::create([
            'member_id' => $member->id,
            'loan_id' => null,
            'amount' => 50000,
            'payment_type' => 1,
            'description' => 'Mobile money collateral test',
            'pay_ref' => $reference,
            'transaction_reference' => $reference,
            'status' => 0,
            'payment_status' => 'Pending',
            'added_by' => $user->id,
            'datecreated' => now(),
        ]);
    }
}
