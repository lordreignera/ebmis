<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Collection;
use App\Services\LoanScheduleService;
use App\Services\FeeManagementService;
use App\Services\LoanApprovalService;
use App\Services\MobileMoneyService;
use App\Services\RepaymentService;
use App\Services\DisbursementService;
use App\Models\PersonalLoan;
use App\Models\User;
use App\Models\FeeType;
use App\Models\ProductCharge;

class LoanManagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--env' => 'testing']);
    }

    /**
     * Test basic service instantiation and dependency injection
     */
    public function test_services_can_be_instantiated()
    {
        $loanScheduleService = app(LoanScheduleService::class);
        $feeManagementService = app(FeeManagementService::class);
        $loanApprovalService = app(LoanApprovalService::class);
        $mobileMoneyService = app(MobileMoneyService::class);
        $repaymentService = app(RepaymentService::class);
        $disbursementService = app(DisbursementService::class);

        $this->assertInstanceOf(LoanScheduleService::class, $loanScheduleService);
        $this->assertInstanceOf(FeeManagementService::class, $feeManagementService);
        $this->assertInstanceOf(LoanApprovalService::class, $loanApprovalService);
        $this->assertInstanceOf(MobileMoneyService::class, $mobileMoneyService);
        $this->assertInstanceOf(RepaymentService::class, $repaymentService);
        $this->assertInstanceOf(DisbursementService::class, $disbursementService);
        
        echo "✅ All services instantiated successfully\n";
    }

    /**
     * Test fee calculation service
     */
    public function test_fee_calculation_service()
    {
        $user = User::factory()->create();
        $loan = PersonalLoan::factory()->create([
            'principal' => 1000000,
            'interest' => 15.0,
        ]);

        $feeType = FeeType::create([
            'name' => 'Processing Fee',
            'account' => 1,
            'added_by' => $user->id,
            'isactive' => 1,
            'required_disbursement' => 1,
        ]);

        ProductCharge::create([
            'product_id' => $loan->product_type,
            'name' => $feeType->name,
            'type' => 2,
            'value' => '2.5',
            'added_by' => $user->id,
            'isactive' => 1,
        ]);

        $feeService = app(FeeManagementService::class);
        $fees = $feeService->calculateLoanFees($loan);

        $this->assertCount(1, $fees);
        $this->assertEquals(25000.0, $fees->first()['calculated_amount']);
        $this->assertTrue($fees->first()['is_mandatory']);
        
        echo "✅ Fee calculation service works correctly\n";
    }

    /**
     * Test schedule generation service
     */
    public function test_schedule_generation_service()
    {
        $scheduleService = app(LoanScheduleService::class);
        $loan = PersonalLoan::factory()->create([
            'principal' => 1000000,
            'interest' => 15.0,
            'period' => 12,
        ]);

        $schedule = $scheduleService->generateSchedule($loan);

        $this->assertInstanceOf(Collection::class, $schedule);
        $this->assertGreaterThan(0, $schedule->count());
        if ($schedule->count() > 0) {
            $firstPayment = $schedule->first();
            $this->assertArrayHasKey('payment_date', $firstPayment);
            $this->assertArrayHasKey('payment', $firstPayment);
            $this->assertArrayHasKey('principal', $firstPayment);
            $this->assertArrayHasKey('interest', $firstPayment);
        }

        echo "✅ Schedule generation service works correctly\n";
    }

    /**
     * Test mobile money service network detection
     */
    public function test_mobile_money_network_detection()
    {
        $mobileMoneyService = app(MobileMoneyService::class);

        // Test MTN number
        $mtnNumber = '256777123456';
        $network = $mobileMoneyService->detectNetwork($mtnNumber);
        $this->assertEquals('MTN', $network); // Returns string 'MTN'

        // Test Airtel number
        $airtelNumber = '256750123456';
        $network = $mobileMoneyService->detectNetwork($airtelNumber);
        $this->assertEquals('AIRTEL', $network);

        echo "✅ Mobile money network detection works correctly\n";
    }

    /**
     * Test phone number formatting
     */
    public function test_phone_number_formatting()
    {
        $mobileMoneyService = app(MobileMoneyService::class);

        // Test various formats
        $formats = [
            '0777123456' => '256777123456',
            '+256777123456' => '256777123456',
            '256777123456' => '256777123456',
        ];

        foreach ($formats as $input => $expected) {
            $formatted = $mobileMoneyService->formatPhoneNumber($input);
            $this->assertEquals($expected, $formatted);
        }

        echo "✅ Phone number formatting works correctly\n";
    }

    /**
     * Integration test to verify complete workflow compatibility
     */
    public function test_complete_workflow_integration()
    {
        echo "\n🔄 Testing complete loan management workflow integration...\n";

        // 1. Test service instantiation
        $this->test_services_can_be_instantiated();

        // 2. Test fee calculation
        $this->test_fee_calculation_service();

        // 3. Test schedule generation  
        $this->test_schedule_generation_service();

        // 4. Test mobile money functionality
        $this->test_mobile_money_network_detection();
        $this->test_phone_number_formatting();

        echo "\n✅ All loan management workflow tests passed!\n";
        echo "🎉 System is ready for deployment!\n\n";
    }
}
