<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Country;
use App\Models\Group;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingModuleRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'user_type' => 'super_admin',
            'status' => 'active',
        ]);
    }

    public function test_meeting_list_pages_render_without_server_errors(): void
    {
        foreach ([
            'admin.savings.index',
            'admin.savings.pending',
            'admin.savings.approved',
            'admin.expenditures.index',
            'admin.repayments.pending',
            'admin.repayments.history',
            'admin.schools.create',
        ] as $routeName) {
            $this->actingAs($this->admin)
                ->get(route($routeName))
                ->assertOk();
        }
    }

    public function test_pending_and_history_urls_resolve_to_their_named_actions(): void
    {
        $this->assertSame('admin.repayments.pending', app('router')->getRoutes()->match(
            request()->create('/admin/repayments/pending', 'GET')
        )->getName());

        $this->assertSame('admin.repayments.history', app('router')->getRoutes()->match(
            request()->create('/admin/repayments/history', 'GET')
        )->getName());

        $this->assertSame('admin.savings.pending', app('router')->getRoutes()->match(
            request()->create('/admin/savings/pending', 'GET')
        )->getName());

        $this->assertSame('admin.savings.approved', app('router')->getRoutes()->match(
            request()->create('/admin/savings/approved', 'GET')
        )->getName());
    }

    public function test_group_can_be_suspended_and_reactivated_using_legacy_verified_field(): void
    {
        $branch = Branch::create(['name' => 'Meeting Test Branch']);
        $group = Group::create([
            'code' => 'GRP-MEETING',
            'name' => 'Meeting Test Group',
            'inception_date' => '2026-01-01',
            'address' => 'Kampala',
            'sector' => 'Trade',
            'type' => 1,
            'verified' => 1,
            'branch_id' => $branch->id,
            'added_by' => $this->admin->id,
            'datecreated' => now(),
        ]);

        $this->assertSame('active', $group->status);

        $this->actingAs($this->admin)
            ->post(route('admin.groups.suspend', $group))
            ->assertRedirect();

        $this->assertSame(2, $group->refresh()->verified);
        $this->assertSame('suspended', $group->status);

        $this->actingAs($this->admin)
            ->post(route('admin.groups.activate', $group))
            ->assertRedirect();

        $this->assertSame(1, $group->refresh()->verified);
        $this->assertSame('active', $group->status);
    }

    public function test_investor_maintenance_status_actions_work(): void
    {
        $country = Country::create([
            'name' => 'Uganda',
            'code' => 'UG',
            'is_active' => true,
        ]);
        $investor = Investor::create([
            'title' => 1,
            'fname' => 'Meeting',
            'lname' => 'Investor',
            'address' => 'Kampala',
            'city' => 'Kampala',
            'country' => $country->id,
            'email' => 'meeting-investor@example.test',
            'passcode' => 1234,
            'phone' => '0700000000',
            'gender' => 'Male',
            'IDtype' => 'National ID',
            'IDnumber' => 'CMTEST123',
            'status' => 0,
            'description' => '',
            'dob' => '1990-01-01',
            'soft_delete' => 0,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.investments.activate-investor', $investor))
            ->assertRedirect();
        $this->assertTrue($investor->refresh()->is_active);

        $this->actingAs($this->admin)
            ->post(route('admin.investments.deactivate-investor', $investor), ['reason' => 'Meeting test'])
            ->assertRedirect();
        $this->assertSame(3, $investor->refresh()->status);
        $this->assertFalse($investor->is_active);

        $investment = Investment::create([
            'userid' => $investor->id,
            'type' => 1,
            'name' => 'Meeting Investment',
            'amount' => '1000000',
            'period' => '2',
            'percentage' => '2.56',
            'start' => '08/27/2026',
            'end' => '08/27/2028',
            'interest' => '51200',
            'details' => 'Regression fixture',
            'status' => 1,
            'added_by' => (string) $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.investments.api.investor-portfolio', $investor))
            ->assertOk()
            ->assertJsonPath('summary.count', 1)
            ->assertJsonPath('investments.0.id', $investment->id);

        $this->actingAs($this->admin)
            ->delete(route('admin.investments.destroy', $investment))
            ->assertRedirect(route('admin.investments.show-investor', $investor));
        $this->assertSame(3, $investment->refresh()->status);

        $this->actingAs($this->admin)
            ->get(route('admin.investments.api.investment-statistics'))
            ->assertOk()
            ->assertJsonStructure([
                'total_investors',
                'active_investors',
                'total_investments',
                'active_investments',
                'pending_investments',
                'total_amount',
                'projected_interest',
            ]);
    }

    public function test_admin_can_create_a_school_and_its_login_account(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.schools.store'), [
                'school_name' => 'Meeting Demonstration School',
                'registration_number' => 'REG-MEETING-01',
                'school_type' => 'Secondary',
                'ownership' => 'Private',
                'contact_person' => 'School Administrator',
                'email' => 'meeting-school@example.test',
                'phone' => '0700111222',
                'physical_address' => 'Kampala Road',
                'district' => 'Kampala',
                'password' => 'MeetingPass123!',
                'password_confirmation' => 'MeetingPass123!',
                'status' => 'approved',
            ]);

        $school = \App\Models\School::where('email', 'meeting-school@example.test')->firstOrFail();

        $response->assertRedirect(route('admin.schools.show', $school));
        $this->assertDatabaseHas('users', [
            'email' => 'meeting-school@example.test',
            'school_id' => $school->id,
            'user_type' => 'school',
            'status' => 'active',
        ]);
        $this->assertSame('approved', $school->status);
    }

    public function test_active_loan_excel_export_downloads_successfully(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.loans.active.export', ['format' => 'excel']))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
    }
}
