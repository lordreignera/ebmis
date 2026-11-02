<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvestorPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_standalone_and_does_not_render_portal_navigation(): void
    {
        $this->get(route('investor.register'))
            ->assertOk()
            ->assertSee('Investor registration')
            ->assertSee('EBIMS Investor Portal')
            ->assertDontSee('Investor login');
    }

    public function test_invalid_registration_is_rejected_without_creating_an_investor(): void
    {
        $country = Country::create(['name' => 'Uganda', 'code' => 'UG']);

        $this->from(route('investor.register'))
            ->post(route('investor.register.store'), [
                'title' => 1,
                'fname' => 'Incomplete',
                'lname' => 'Registration',
                'email' => 'invalid@example.test',
                'phone' => '0700000000',
                'gender' => 'Female',
                'dob' => now()->addDay()->toDateString(),
                'address' => 'Kampala Road',
                'city' => 'Kampala',
                'country' => $country->id,
                'IDtype' => 'National ID',
                'IDnumber' => 'INVALID-001',
                'password' => 'weak',
                'password_confirmation' => 'different',
            ])
            ->assertRedirect(route('investor.register'))
            ->assertSessionHasErrors(['dob', 'id_document', 'profile_photo', 'password', 'terms']);

        $this->assertDatabaseMissing('investors', ['email' => 'invalid@example.test']);
    }

    public function test_duplicate_registration_is_blocked_and_returns_the_existing_status(): void
    {
        $existing = $this->createInvestor('pending');

        $this->post(route('investor.register.store'), [
            'email' => $existing->email,
            'IDnumber' => 'A-DIFFERENT-ID',
        ])->assertRedirect(route('investor.register'))
            ->assertSessionHasErrors('email')
            ->assertSessionHas('registration_status.status', 'pending');

        $this->assertSame(1, Investor::where('email', $existing->email)->count());

        $this->post(route('investor.registration-status'), [
            'status_IDnumber' => $existing->IDnumber,
        ])->assertRedirect(route('investor.register'))
            ->assertSessionHas('registration_status.status', 'pending');

        $approved = $this->createInvestor('approved');

        $this->post(route('investor.registration-status'), [
            'status_email' => strtoupper($approved->email),
        ])->assertRedirect(route('investor.register'))
            ->assertSessionHas('registration_status.status', 'approved');
    }

    public function test_investor_registration_creates_a_pending_account_with_private_kyc_files(): void
    {
        Storage::fake('local');
        $country = Country::create(['name' => 'Uganda', 'code' => 'UG']);

        $response = $this->post(route('investor.register.store'), [
            'title' => 1,
            'fname' => 'Jane',
            'lname' => 'Investor',
            'email' => 'jane@example.test',
            'phone' => '0700000000',
            'gender' => 'Female',
            'dob' => '1990-04-12',
            'address' => 'Plot 1, Kampala Road',
            'city' => 'Kampala',
            'country' => $country->id,
            'zip' => '256',
            'IDtype' => 'National ID',
            'IDnumber' => 'CM90000001',
            'id_document' => UploadedFile::fake()->create('national-id.pdf', 100, 'application/pdf'),
            'profile_photo' => UploadedFile::fake()->image('profile.jpg'),
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('investor.registration-submitted'));
        $investor = Investor::where('email', 'jane@example.test')->firstOrFail();

        $this->assertSame('pending', $investor->portal_status);
        $this->assertSame(0, $investor->status);
        $this->assertTrue(Hash::check('SecurePass1', $investor->password));
        Storage::disk('local')->assertExists($investor->image_path);
        Storage::disk('local')->assertExists($investor->photo_path);
    }

    public function test_pending_investor_cannot_log_in(): void
    {
        $investor = $this->createInvestor('pending');

        $response = $this->post(route('investor.login.store'), [
            'email' => $investor->email,
            'password' => 'SecurePass1',
        ]);

        $response->assertSessionHas('warning');
        $this->assertGuest('investor');
    }

    public function test_rejected_investor_cannot_log_in(): void
    {
        $investor = $this->createInvestor('rejected');

        $response = $this->post(route('investor.login.store'), [
            'email' => $investor->email,
            'password' => 'SecurePass1',
        ]);

        $response->assertSessionHas('error');
        $this->assertGuest('investor');
    }

    public function test_direct_dashboard_access_requires_an_approved_investor_session(): void
    {
        $this->get(route('investor.dashboard'))
            ->assertRedirect(route('investor.login'));

        $investor = $this->createInvestor('approved');
        $investor->update(['status' => 0]);

        $this->actingAs($investor, 'investor')
            ->get(route('investor.dashboard'))
            ->assertRedirect(route('investor.login'))
            ->assertSessionHas('error');

        $this->assertGuest('investor');
    }

    public function test_only_a_super_administrator_can_approve_an_investor(): void
    {
        Storage::fake('local');
        $investor = $this->createInvestor('pending');
        $investor->update([
            'image_path' => 'investor-kyc/'.$investor->id.'/identity.pdf',
            'photo_path' => 'investor-kyc/'.$investor->id.'/photo.jpg',
        ]);
        Storage::disk('local')->put($investor->image_path, 'identity');
        Storage::disk('local')->put($investor->photo_path, 'photo');
        $staff = User::factory()->create(['user_type' => 'branch', 'status' => 'active']);

        $this->actingAs($staff)
            ->post(route('admin.investments.investor-approvals.approve', $investor))
            ->assertForbidden();

        $superAdmin = User::factory()->create(['user_type' => 'super_admin', 'status' => 'active']);

        $this->actingAs($superAdmin)
            ->get(route('admin.investments.investor-approvals.index'))
            ->assertOk()
            ->assertSee('Uganda')
            ->assertSee('Back to Access Control')
            ->assertSee('View all investor details')
            ->assertSee('investorDetailsModal')
            ->assertSee('data-bs-toggle="modal"', false)
            ->assertDontSee('data-toggle="modal"', false);

        $this->actingAs($superAdmin)
            ->post(route('admin.investments.investor-approvals.approve', $investor))
            ->assertRedirect();

        $investor->refresh();
        $this->assertSame('approved', $investor->portal_status);
        $this->assertSame(1, $investor->status);
        $this->assertSame($superAdmin->id, $investor->approved_by);
        $this->assertNotNull($investor->approved_at);

        $this->post(route('investor.login.store'), [
            'email' => $investor->email,
            'password' => 'SecurePass1',
        ])->assertRedirect(route('investor.dashboard'));

        $this->assertAuthenticatedAs($investor, 'investor');
    }

    public function test_super_administrator_cannot_approve_incomplete_kyc_registration(): void
    {
        Storage::fake('local');
        $investor = $this->createInvestor('pending');
        $superAdmin = User::factory()->create(['user_type' => 'super_admin', 'status' => 'active']);

        $this->actingAs($superAdmin)
            ->post(route('admin.investments.investor-approvals.approve', $investor))
            ->assertSessionHasErrors('approval');

        $this->assertSame('pending', $investor->fresh()->portal_status);
    }

    public function test_approved_investor_can_log_in_and_open_their_dashboard(): void
    {
        $investor = $this->createInvestor('approved');

        $response = $this->post(route('investor.login.store'), [
            'email' => $investor->email,
            'password' => 'SecurePass1',
        ]);

        $response->assertRedirect(route('investor.dashboard'));
        $this->assertAuthenticatedAs($investor, 'investor');

        $this->get(route('investor.dashboard'))
            ->assertOk()
            ->assertSee('Welcome, '.$investor->fname);
    }

    private function createInvestor(string $portalStatus): Investor
    {
        $country = Country::firstOrCreate(
            ['code' => 'UG'],
            ['name' => 'Uganda']
        );

        return Investor::create([
            'title' => 1,
            'fname' => 'Test',
            'lname' => 'Investor',
            'address' => 'Kampala Road',
            'city' => 'Kampala',
            'country' => $country->id,
            'email' => uniqid('investor-', true).'@example.test',
            'password' => Hash::make('SecurePass1'),
            'passcode' => 123456,
            'phone' => '0700000000',
            'gender' => 'Female',
            'IDtype' => 'National ID',
            'IDnumber' => uniqid('CM'),
            'status' => $portalStatus === 'approved' ? 1 : 0,
            'portal_status' => $portalStatus,
            'description' => '',
            'dob' => '1990-04-12',
            'soft_delete' => 0,
        ]);
    }
}
