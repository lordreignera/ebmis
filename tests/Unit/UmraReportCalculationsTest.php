<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\PersonalLoan;
use App\Models\Member;
use App\Http\Controllers\Admin\UmraReportController;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UmraReportCalculationsTest extends TestCase
{
    use RefreshDatabase;

    protected UmraReportController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new UmraReportController();
    }

    private function createLoanSchedule(array $attributes): LoanSchedule
    {
        $attributes['payment'] = $attributes['payment']
            ?? (($attributes['principal'] ?? 0) + ($attributes['interest'] ?? 0));

        return LoanSchedule::create($attributes);
    }

    /**
     * Test that only unpaid schedules are counted in outstanding principal
     */
    public function test_outstanding_principal_excludes_paid_schedules()
    {
        // Create a test loan with schedules
        $member = Member::factory()->create();
        $loan = PersonalLoan::factory()->create([
            'member_id' => $member->id,
            'status' => 2, // Disbursed/running
            'principal' => 100000,
        ]);

        // Create 2 schedules: one paid, one unpaid
        $this->createLoanSchedule([
            'loan_id' => $loan->id,
            'payment_date' => now()->subDays(10),
            'principal' => 50000,
            'interest' => 5000,
            'balance' => 0,
            'status' => 1, // PAID
            'paid' => 55000,
        ]);

        $this->createLoanSchedule([
            'loan_id' => $loan->id,
            'payment_date' => now()->addDays(5),
            'principal' => 50000,
            'interest' => 5000,
            'balance' => 55000,
            'status' => 0, // UNPAID
            'paid' => 0,
        ]);

        // Call the dashboard method
        $response = $this->controller->dashboard();
        
        // Get the indicators from view data
        $indicators = $response->getData()['indicators'];

        // Outstanding principal should only include unpaid schedule (50000)
        $expectedPrincipal = 50000;
        $this->assertEquals(
            number_format($expectedPrincipal, 2),
            $indicators['gross_outstanding_principal'],
            'Outstanding principal should only include unpaid schedules'
        );

        // Interest outstanding should be 5000
        $this->assertEquals(
            number_format(5000, 2),
            $indicators['interest_outstanding'],
            'Interest outstanding should only include unpaid schedules'
        );
    }

    /**
     * Test that partial payments reduce outstanding on unpaid schedules.
     */
    public function test_outstanding_balance_uses_remaining_unpaid_schedule_amount()
    {
        $member = Member::factory()->create();
        $loan = PersonalLoan::factory()->create([
            'member_id' => $member->id,
            'status' => 2,
            'principal' => 100000,
        ]);

        $this->createLoanSchedule([
            'loan_id' => $loan->id,
            'payment_date' => now()->addDays(5),
            'principal' => 100000,
            'interest' => 20000,
            'balance' => 90000,
            'status' => 0,
            'paid' => 30000,
        ]);

        $response = $this->controller->dashboard();
        $indicators = $response->getData()['indicators'];

        $this->assertEquals(
            number_format(90000, 2),
            $indicators['gross_outstanding_principal'],
            'Partial payments should reduce outstanding principal after interest is covered'
        );

        $this->assertEquals(
            number_format(0, 2),
            $indicators['interest_outstanding'],
            'Partial payments should reduce outstanding interest first'
        );
    }

    /**
     * Test PAR 30 calculation - loans 31+ days late
     */
    public function test_par_30_calculation()
    {
        // Create 10 active loans
        $member = Member::factory()->create();
        for ($i = 0; $i < 10; $i++) {
            $loan = PersonalLoan::factory()->create([
                'member_id' => $member->id,
                'status' => 2, // Disbursed/running
            ]);

            // Create schedules
            if ($i < 3) {
                // First 3 loans: 35+ days overdue (PAR 30)
                $this->createLoanSchedule([
                    'loan_id' => $loan->id,
                    'payment_date' => now()->subDays(35),
                    'principal' => 50000,
                    'interest' => 5000,
                    'balance' => 55000,
                    'status' => 0, // UNPAID
                ]);
            } else {
                // Rest: current or early
                $this->createLoanSchedule([
                    'loan_id' => $loan->id,
                    'payment_date' => now()->addDays(5),
                    'principal' => 50000,
                    'interest' => 5000,
                    'balance' => 55000,
                    'status' => 0, // UNPAID
                ]);
            }
        }

        $response = $this->controller->dashboard();
        $indicators = $response->getData()['indicators'];

        // PAR 30 should be 30% (3 out of 10)
        $this->assertEquals(
            number_format(30.0, 1),
            $indicators['par_30'],
            'PAR 30 should be 30% (3 loans out of 10 are 31+ days late)'
        );
    }

    /**
     * Test PAR 90 calculation - loans 91+ days late
     */
    public function test_par_90_calculation()
    {
        $member = Member::factory()->create();
        for ($i = 0; $i < 10; $i++) {
            $loan = PersonalLoan::factory()->create([
                'member_id' => $member->id,
                'status' => 2, // Disbursed/running
            ]);

            if ($i < 2) {
                // First 2 loans: 95+ days overdue (PAR 90)
                $this->createLoanSchedule([
                    'loan_id' => $loan->id,
                    'payment_date' => now()->subDays(95),
                    'principal' => 50000,
                    'interest' => 5000,
                    'balance' => 55000,
                    'status' => 0, // UNPAID
                ]);
            } else {
                // Rest: current or early
                $this->createLoanSchedule([
                    'loan_id' => $loan->id,
                    'payment_date' => now()->addDays(5),
                    'principal' => 50000,
                    'interest' => 5000,
                    'balance' => 55000,
                    'status' => 0, // UNPAID
                ]);
            }
        }

        $response = $this->controller->dashboard();
        $indicators = $response->getData()['indicators'];

        // PAR 90 should be 20% (2 out of 10)
        $this->assertEquals(
            number_format(20.0, 1),
            $indicators['par_90'],
            'PAR 90 should be 20% (2 loans out of 10 are 91+ days late)'
        );
    }

    /**
     * Test provision calculation - UMRA regulation compliance
     */
    public function test_provision_calculation_umra_regulations()
    {
        $member = Member::factory()->create();

        // Create loans with different UMRA overdue statuses
        $loans = [
            ['days' => -5, 'rate' => 0.01],   // Performing: 1%
            ['days' => 15, 'rate' => 0.05],   // Watch: 5%
            ['days' => 50, 'rate' => 0.25],   // Substandard: 25%
            ['days' => 120, 'rate' => 0.50],  // Doubtful: 50%
            ['days' => 200, 'rate' => 1.00],  // Loss: 100%
        ];

        $expectedProvision = 0;

        foreach ($loans as $index => $loan) {
            $loanRecord = PersonalLoan::factory()->create([
                'member_id' => $member->id,
                'status' => 2,
            ]);

            $balance = 100000;
            $expectedProvision += $balance * $loan['rate'];

            $this->createLoanSchedule([
                'loan_id' => $loanRecord->id,
                'payment_date' => now()->subDays($loan['days']),
                'principal' => 50000,
                'interest' => 50000,
                'balance' => $balance,
                'status' => 0, // UNPAID
            ]);
        }

        $response = $this->controller->dashboard();
        $indicators = $response->getData()['indicators'];

        // Extract numeric value from formatted string
        $actualProvision = (float) str_replace(',', '', $indicators['required_provision']);

        $this->assertEquals(
            $expectedProvision,
            $actualProvision,
            "Required provision should be " . number_format($expectedProvision, 2) . " per UMRA regulations"
        );
    }

    /**
     * Test loss classified exposure - 180+ days overdue
     */
    public function test_loss_classified_exposure()
    {
        $member = Member::factory()->create();
        
        // Create 2 loans: one in loss category, one not
        $loanInLoss = PersonalLoan::factory()->create([
            'member_id' => $member->id,
            'status' => 2,
        ]);

        $loanNotInLoss = PersonalLoan::factory()->create([
            'member_id' => $member->id,
            'status' => 2,
        ]);

        // Schedule 180+ days overdue
        $this->createLoanSchedule([
            'loan_id' => $loanInLoss->id,
            'payment_date' => now()->subDays(185),
            'principal' => 60000,
            'interest' => 10000,
            'balance' => 70000,
            'status' => 0, // UNPAID
        ]);

        // Schedule not overdue
        $this->createLoanSchedule([
            'loan_id' => $loanNotInLoss->id,
            'payment_date' => now()->addDays(5),
            'principal' => 50000,
            'interest' => 5000,
            'balance' => 55000,
            'status' => 0, // UNPAID
        ]);

        $response = $this->controller->dashboard();
        $indicators = $response->getData()['indicators'];

        $actualLossExposure = (float) str_replace(',', '', $indicators['loss_classified_exposure']);

        // Should only count the 185 days overdue schedule (60000 + 10000)
        $this->assertEquals(
            70000.0,
            $actualLossExposure,
            'Loss classified exposure should include only 180+ days overdue schedules'
        );
    }

    /**
     * Test that closed loans (status 3) are excluded from outstanding calculations
     */
    public function test_closed_loans_excluded()
    {
        $member = Member::factory()->create();

        // Create active loan
        $activeLoan = PersonalLoan::factory()->create([
            'member_id' => $member->id,
            'status' => 2,
        ]);

        // Create closed loan
        $closedLoan = PersonalLoan::factory()->create([
            'member_id' => $member->id,
            'status' => 3, // CLOSED
        ]);

        // Active loan has an unpaid schedule; closed loan is fully paid.
        $this->createLoanSchedule([
            'loan_id' => $activeLoan->id,
            'payment_date' => now()->subDays(10),
            'principal' => 50000,
            'interest' => 5000,
            'balance' => 55000,
            'status' => 0,
        ]);

        $this->createLoanSchedule([
            'loan_id' => $closedLoan->id,
            'payment_date' => now()->subDays(10),
            'principal' => 50000,
            'interest' => 5000,
            'balance' => 0,
            'status' => 1,
            'paid' => 55000,
        ]);

        $response = $this->controller->dashboard();
        $indicators = $response->getData()['indicators'];

        $actualPrincipal = (float) str_replace(',', '', $indicators['gross_outstanding_principal']);

        // Should only count active loan principal
        $this->assertEquals(
            50000.0,
            $actualPrincipal,
            'Outstanding principal should exclude closed loans'
        );
    }

    /**
     * Test total active loan accounts count
     */
    public function test_total_active_loan_accounts()
    {
        $member = Member::factory()->create();

        // Create 5 active loans and 3 inactive
        for ($i = 0; $i < 5; $i++) {
            $loan = PersonalLoan::factory()->create([
                'member_id' => $member->id,
                'status' => 2, // Disbursed/running
            ]);

            $this->createLoanSchedule([
                'loan_id' => $loan->id,
                'payment_date' => now()->addDays(5),
                'principal' => 50000,
                'interest' => 5000,
                'balance' => 55000,
                'status' => 0,
            ]);
        }

        for ($i = 0; $i < 3; $i++) {
            PersonalLoan::factory()->create([
                'member_id' => $member->id,
                'status' => 0, // Inactive
            ]);
        }

        $response = $this->controller->dashboard();
        $indicators = $response->getData()['indicators'];

        $this->assertEquals(
            5,
            $indicators['total_active_loan_accounts'],
            'Total active loan accounts should be 5'
        );
    }
}
