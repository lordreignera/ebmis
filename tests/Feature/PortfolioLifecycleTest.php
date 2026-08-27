<?php

namespace Tests\Feature;

use App\Models\PersonalLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PortfolioLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_portfolio_route_and_module_entry_are_removed(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin', 'status' => 'active']);

        $this->assertFalse(Route::has('admin.portfolio.individual'));

        $this->actingAs($admin)
            ->get('/admin/portfolio/individual')
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.modules.loan-portfolio'))
            ->assertOk()
            ->assertDontSee('Personal Portfolio')
            ->assertSee('Status 0')
            ->assertSee('Status 6')
            ->assertSee('Overdue');
    }

    public function test_every_portfolio_lifecycle_destination_renders(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin', 'status' => 'active']);

        foreach ([
            'admin.portfolio.pending',
            'admin.portfolio.approved',
            'admin.portfolio.running',
            'admin.portfolio.overdue',
            'admin.portfolio.paid',
            'admin.portfolio.rejected',
            'admin.portfolio.restructured',
            'admin.portfolio.stopped',
            'admin.portfolio.branch',
            'admin.portfolio.product',
            'admin.portfolio.group',
        ] as $routeName) {
            $this->actingAs($admin)
                ->get(route($routeName))
                ->assertOk();
        }
    }

    public function test_restructured_portfolio_lists_replacement_loans_instead_of_originals(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin', 'status' => 'active']);

        PersonalLoan::factory()->create([
            'code' => 'RRLOAN-REPLACEMENT-001',
            'status' => 2,
            'restructured' => 1,
            'OLoanID' => 'PLOAN-ORIGINAL-001',
        ]);

        PersonalLoan::factory()->create([
            'code' => 'PLOAN-ORIGINAL-001',
            'status' => 5,
            'restructured' => 1,
        ]);

        PersonalLoan::factory()->create([
            'code' => 'PLOAN-NORMAL-ACTIVE-001',
            'status' => 2,
            'restructured' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.portfolio.restructured'))
            ->assertOk()
            ->assertSee('RRLOAN-REPLACEMENT-001')
            ->assertDontSee('PLOAN-ORIGINAL-001')
            ->assertDontSee('PLOAN-NORMAL-ACTIVE-001')
            ->assertSee('Restructured')
            ->assertSee('Current state: Active / Disbursed');
    }
}
