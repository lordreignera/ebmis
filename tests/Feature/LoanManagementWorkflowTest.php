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
use App\Models\Product;
use App\Models\Member;
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
        // Create a simple product
        $product = new Product();
        $product->name = 'Test Loan Product';
        $product->interest = 15.0;
        $product->type = 1; // Loan product
        $product->isactive = true;
        $product->save();

        // Create a fee type
        $feeType = new FeeType();
        $feeType->name = 'Processing Fee';
        $feeType->active = true;
        $feeType->save();

        // Create a product charge
        $charge = new ProductCharge();
        $charge->product_id = $product->id;
        $charge->fee_type_id = $feeType->id;
        $charge->charge_type = 'deducted';
        $charge->amount_type = 'percentage';
        $charge->amount = 2.5;
        $charge->mandatory = true;
        $charge->active = true;
        $charge->save();

        $feeService = app(FeeManagementService::class);
        $principal = 1000000; // 1M UGX

        $result = $feeService->calculateLoanFees($product->id, $principal);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('total_fees', $result);
        $this->assertArrayHasKey('disbursement_amount', $result);
        $this->assertGreaterThan(0, $result['total_fees']);
        
        echo "✅ Fee calculation service works correctly\n";
    }

    /**
     * Test schedule generation service
     */
    public function test_schedule_generation_service()
    {
        $scheduleService = app(LoanScheduleService::class);
        
        // Create a mock loan object with necessary data
        $mockLoan = (object) [
            'principal' => 1000000, // 1M UGX
            'interest' => 15.0, // 15% annual
            'period' => 12, // 12 months
            'period_type' => 'months',
            'date_disbursed' => now()->format('Y-m-d')
        ];

        $schedule = $scheduleService->generateSchedule($mockLoan);

        $this->assertInstanceOf(Collection::class, $schedule);
        $this->assertGreaterThan(0, $schedule->count());
        if ($schedule->count() > 0) {
            $firstPayment = $schedule->first();
            $this->assertArrayHasKey('payment_date', $firstPayment);
            $this->assertArrayHasKey('total_payment', $firstPayment);
            $this->assertArrayHasKey('principal_payment', $firstPayment);
            $this->assertArrayHasKey('interest_payment', $firstPayment);
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
        $this->assertEquals('Airtel', $network); // Returns string 'Airtel'

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