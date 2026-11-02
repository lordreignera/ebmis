<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InvestmentManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_calculation_route_reaches_the_calculation_action(): void
    {
        $admin = User::factory()->create([
            'user_type' => 'super_admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.investments.calculate-returns'), [
                'amount' => 10000,
                'period' => 3,
                'type' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('type_name', 'Standard Interest')
            ->assertJsonPath('period', '3 years')
            ->assertJsonPath('formatted.amount', 'UGX 10,000.00');
    }

    public function test_investor_and_investment_detail_pages_render_current_schema_fields(): void
    {
        $admin = User::factory()->create([
            'user_type' => 'super_admin',
            'status' => 'active',
        ]);
        $country = Country::create(['name' => 'Uganda', 'code' => 'UG']);
        $investor = Investor::create($this->investorAttributes($country->id));
        $investment = Investment::create([
            'userid' => $investor->id,
            'type' => 1,
            'name' => 'Working capital fund',
            'amount' => 10000,
            'period' => 3,
            'percentage' => 3.7,
            'start' => now()->format('m/d/Y'),
            'end' => now()->addYears(3)->format('m/d/Y'),
            'interest' => 1110,
            'details' => 'Bank transfer',
            'status' => 1,
            'added_by' => 'Test Administrator',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.investments.show-investor', $investor))
            ->assertOk()
            ->assertSee('Working capital fund')
            ->assertSee($investor->full_name);

        $this->actingAs($admin)
            ->get(route('admin.investments.show-investment', $investment))
            ->assertOk()
            ->assertSee('Working capital fund')
            ->assertSee($investor->full_name);
    }

    public function test_storing_an_investment_writes_the_investment_and_investor_areas_atomically(): void
    {
        $admin = User::factory()->create([
            'user_type' => 'super_admin',
            'status' => 'active',
        ]);
        $country = Country::create(['name' => 'Uganda', 'code' => 'UG']);
        $investor = Investor::create($this->investorAttributes($country->id));

        $this->actingAs($admin)
            ->post(route('admin.investments.store-investment', $investor), [
                'inv_name' => 'Agriculture fund',
                'inv_amt' => 15000,
                'inv_period' => 3,
                'inv_term' => 1,
                'areas' => ['Farming', 'Education'],
                'details' => 'Bank transfer reference pending.',
            ])
            ->assertRedirect(route('admin.investments.show-investor', $investor));

        $investment = Investment::where('userid', $investor->id)->firstOrFail();

        $this->assertSame('Agriculture fund', $investment->name);
        $this->assertSame(1, (int) $investment->status);
        $this->assertSame(
            ['Education', 'Farming'],
            DB::table('areas_of_interest')
                ->where('inv_id', $investor->id)
                ->orderBy('area')
                ->pluck('area')
                ->all()
        );
    }

    public function test_investor_deletion_requires_the_authenticated_administrator_password(): void
    {
        $admin = User::factory()->create([
            'user_type' => 'super_admin',
            'status' => 'active',
            'password' => Hash::make('AdminPassword1'),
        ]);
        $country = Country::create(['name' => 'Uganda', 'code' => 'UG']);
        $investor = Investor::create($this->investorAttributes($country->id));

        $this->actingAs($admin)
            ->post(route('admin.investments.delete-investor', $investor), [
                'code' => 'wrong-password',
                'del_comments' => 'Duplicate record',
            ])
            ->assertSessionHasErrors('code');

        $this->assertSame(0, (int) $investor->fresh()->soft_delete);
    }

    private function investorAttributes(int $countryId): array
    {
        return [
            'title' => 1,
            'fname' => 'Jane',
            'lname' => 'Investor',
            'address' => 'Kampala Road',
            'city' => 'Kampala',
            'country' => $countryId,
            'email' => uniqid('investor-', true).'@example.test',
            'password' => Hash::make('SecurePass1'),
            'passcode' => 123456,
            'phone' => '0700000000',
            'gender' => 'Female',
            'IDtype' => 'National ID',
            'IDnumber' => uniqid('CM'),
            'status' => 1,
            'portal_status' => 'approved',
            'description' => 'Test investor',
            'dob' => '1990-04-12',
            'soft_delete' => 0,
        ];
    }
}
