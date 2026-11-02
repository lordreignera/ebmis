<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessControlControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_users_cannot_access_staff_or_admin_pages(): void
    {
        foreach ([
            route('dashboard'),
            route('admin.home'),
            route('admin.modules.dashboard'),
            route('admin.investments.index'),
            route('admin.access-control.index'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_access_control_pages_render_for_super_admin(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin']);
        $role = Role::create(['name' => 'Test Branch Manager', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'test-view-members', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Test Branch Manager')
            ->assertSee('Roles & Permissions', false)
            ->assertSee('QUICK ACTIONS');

        $this->actingAs($admin)
            ->get(route('admin.roles.edit', $role))
            ->assertOk()
            ->assertSee('Edit Role: Test Branch Manager')
            ->assertSee('test-view-members');

        $this->actingAs($admin)
            ->get(route('admin.permissions.index', ['search' => 'test-view-members']))
            ->assertOk()
            ->assertSee('All Permissions')
            ->assertSee('test-view-members');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($admin->email);

        $this->actingAs($admin)
            ->get(route('admin.access-control.index'))
            ->assertOk()
            ->assertSee('Investor Approvals')
            ->assertSee(route('admin.investments.investor-approvals.index'), false);
    }

    public function test_role_pages_only_show_web_guard_roles(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin']);
        Role::create(['name' => 'Test Web Role', 'guard_name' => 'web']);
        Role::create(['name' => 'Test Api Robot', 'guard_name' => 'api']);

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Test Web Role')
            ->assertDontSee('Test Api Robot');
    }

    public function test_system_settings_allow_super_admin_and_administrator_only(): void
    {
        $superAdmin = User::factory()->create(['user_type' => 'super_admin']);
        $administrator = User::factory()->create(['user_type' => 'branch']);
        $administrator->assignRole(Role::create(['name' => 'Administrator', 'guard_name' => 'web']));
        $branchUser = User::factory()->create(['user_type' => 'branch']);

        $this->actingAs($superAdmin)
            ->get(route('admin.settings.dashboard'))
            ->assertOk();

        $this->actingAs($administrator)
            ->get(route('admin.settings.dashboard'))
            ->assertOk();

        $this->actingAs($branchUser)
            ->get(route('admin.settings.dashboard'))
            ->assertForbidden();
    }

    public function test_administrator_system_settings_access_does_not_open_super_admin_access_control(): void
    {
        $administrator = User::factory()->create(['user_type' => 'branch']);
        $administrator->assignRole(Role::create(['name' => 'Administrator', 'guard_name' => 'web']));

        $this->actingAs($administrator)
            ->get(route('admin.access-control.index'))
            ->assertForbidden();
    }

    public function test_role_update_syncs_web_guard_permissions(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin']);
        $role = Role::create(['name' => 'Test Loan Officer', 'guard_name' => 'web']);
        $viewMembers = Permission::create(['name' => 'test-view-members', 'guard_name' => 'web']);
        $manageLoans = Permission::create(['name' => 'test-manage-loans', 'guard_name' => 'web']);
        $role->givePermissionTo($manageLoans);

        $this->actingAs($admin)
            ->put(route('admin.roles.update', $role), [
                'name' => 'Test Loan Officer',
                'permissions' => [$viewMembers->name],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $role->refresh();

        $this->assertTrue($role->hasPermissionTo($viewMembers));
        $this->assertFalse($role->hasPermissionTo($manageLoans));
    }

    public function test_super_admin_can_save_role_permissions_from_role_manager(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin']);
        $role = Role::create(['name' => 'Test Matrix Role', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'test-matrix-permission', 'guard_name' => 'web']);

        $this->actingAs($admin)
            ->post(route('admin.permissions.role.store'), [
                'role_id' => $role->id,
                'permission_ids' => [$permission->id],
                'permissions' => [$permission->id],
            ])
            ->assertRedirect(route('admin.permissions.index', ['role' => $role->id]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($role->fresh()->hasPermissionTo($permission));
    }

    public function test_super_admin_can_create_a_role_and_user_who_can_log_in_to_an_assigned_screen_only(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Role Access Test Branch']);

        $this->actingAs($admin)
            ->get(route('admin.roles.create'))
            ->assertOk()
            ->assertSee('Assign permissions after creation')
            ->assertSee('Manage Permissions');

        $this->post(route('admin.roles.store'), [
            'name' => 'Test Investment Reviewer',
            'permissions' => ['manage-investments'],
        ])->assertRedirect(route('admin.roles.index'))->assertSessionHasNoErrors();

        $role = Role::findByName('Test Investment Reviewer', 'web');
        $this->assertTrue($role->hasPermissionTo('manage-investments'));
        $this->assertTrue($role->hasPermissionTo('access-ebmis-modules'));
        $this->assertFalse($role->hasPermissionTo('view-branch-dashboard'));

        $this->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Test Investment Reviewer');

        $this->post(route('admin.users.store'), [
            'name' => 'Investment Reviewer',
            'email' => 'reviewer@example.test',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'user_type' => 'branch',
            'branch_id' => $branch->id,
            'roles' => ['Test Investment Reviewer'],
        ])->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();

        $user = User::where('email', 'reviewer@example.test')->firstOrFail();
        $this->assertSame('active', $user->status);
        $this->assertTrue($user->hasRole($role));

        auth('web')->logout();
        session()->invalidate();
        app('auth')->forgetGuards();
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'SecurePass123',
        ])->assertRedirect(route('dashboard', absolute: false));

        app('auth')->forgetGuards();
        $this->assertAuthenticatedAs($user, 'web');
        $this->get(route('dashboard'))->assertRedirect(route('admin.home'));
        $this->get(route('admin.modules.dashboard'))->assertOk()->assertSee('Investments Module');
        $this->get(route('admin.investments.index'))
            ->assertOk()
            ->assertDontSee('QUICK ACTIONS')
            ->assertDontSee('id="globalSearchForm"', false)
            ->assertDontSee('needing FO/scoring attention');
        $this->get(route('admin.home'))
            ->assertOk()
            ->assertSee('Welcome to EBIMS')
            ->assertDontSee('My Collections Dashboard')
            ->assertSee('Investments Module')
            ->assertDontSee('Clients Module')
            ->assertDontSee('Loan Portfolio');
        $this->get(route('admin.members.index'))->assertForbidden();
        $this->get(route('admin.access-control.index'))->assertForbidden();
    }

    public function test_updating_a_role_grants_and_revokes_its_screen_access_for_assigned_users(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin', 'status' => 'active']);
        $user = User::factory()->create(['user_type' => 'branch', 'status' => 'active']);
        $role = Role::create(['name' => 'Test Evolving Role', 'guard_name' => 'web']);
        $user->assignRole($role);

        $this->actingAs($user)->get(route('admin.modules.investments'))->assertForbidden();

        $this->actingAs($admin)->put(route('admin.roles.update', $role), [
            'name' => $role->name,
            'permissions' => ['manage-investments'],
        ])->assertRedirect(route('admin.roles.index'))->assertSessionHasNoErrors();

        $this->assertTrue($role->fresh()->hasPermissionTo('access-ebmis-modules'));
        $this->actingAs($user->fresh())->get(route('admin.modules.investments'))->assertOk();

        $this->actingAs($admin)->put(route('admin.roles.update', $role), [
            'name' => $role->name,
            'permissions' => [],
        ])->assertRedirect(route('admin.roles.index'))->assertSessionHasNoErrors();

        $this->assertFalse($role->fresh()->hasPermissionTo('access-ebmis-modules'));
        $this->actingAs($user->fresh())->get(route('admin.modules.investments'))->assertForbidden();
    }

    public function test_existing_module_permissions_are_enough_for_workspace_access(): void
    {
        $user = User::factory()->create(['user_type' => 'branch', 'status' => 'active']);
        $role = Role::create(['name' => 'Legacy Cashier Role', 'guard_name' => 'web']);
        $role->givePermissionTo([
            Permission::findOrCreate('view-accounting-reports', 'web'),
            Permission::findOrCreate('manage-expenditures', 'web'),
        ]);
        $user->assignRole($role);

        $this->assertFalse($role->fresh()->hasPermissionTo('access-ebmis-modules'));

        $this->actingAs($user)
            ->get(route('admin.home'))
            ->assertOk()
            ->assertSee('Reports &amp; Accounting', false)
            ->assertSee('Expenses &amp; Staff Payments', false);

        $this->get(route('admin.modules.reports-accounting'))->assertOk();
        $this->get(route('admin.modules.expenditures'))->assertOk();
    }

    public function test_super_admin_can_assign_a_new_role_to_an_existing_user_from_the_edit_screen(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Existing User Access Branch']);
        $user = User::factory()->create([
            'user_type' => 'branch',
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Test Existing User Investment Role',
            'permissions' => ['manage-investments'],
        ])->assertRedirect(route('admin.roles.index'));

        $this->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('Test Existing User Investment Role');

        $details = [
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => 'branch',
            'branch_id' => $branch->id,
            'status' => 'active',
        ];

        $this->put(route('admin.users.update', $user), $details + [
            'roles' => ['Test Existing User Investment Role'],
        ])->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();

        $this->actingAs($user->fresh())->get(route('admin.investments.index'))->assertOk();
        $this->get(route('admin.members.index'))->assertForbidden();

        $this->actingAs($admin)->put(route('admin.users.update', $user), $details + [
            'roles' => [],
        ])->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();

        $this->actingAs($user->fresh())->get(route('admin.investments.index'))->assertForbidden();
    }

    public function test_dashboard_permission_keeps_the_operational_home_as_the_login_destination(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin', 'status' => 'active']);

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Test Dashboard Viewer',
            'permissions' => ['view-branch-dashboard'],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::findByName('Test Dashboard Viewer', 'web');
        $this->assertTrue($role->hasPermissionTo('access-ebmis-modules'));

        $user = User::factory()->create(['user_type' => 'branch', 'status' => 'active']);
        $user->assignRole($role);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('admin.home'));
    }

    public function test_every_non_school_user_starts_on_the_ebims_home_dashboard(): void
    {
        $user = User::factory()->create(['user_type' => 'branch', 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.home'));

        $this->get(route('admin.home'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertDontSee('All EBIMS Modules')
            ->assertDontSee('Investments Module');
        $this->get(route('admin.modules.dashboard'))->assertForbidden();
    }

    public function test_custom_unmapped_permissions_do_not_open_operational_routes(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin', 'status' => 'active']);

        $this->actingAs($admin)->post(route('admin.permissions.store'), [
            'name' => 'test-custom-feature',
        ])->assertRedirect(route('admin.permissions.index'));

        $this->post(route('admin.roles.store'), [
            'name' => 'Test Custom Only',
            'permissions' => ['test-custom-feature'],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::findByName('Test Custom Only', 'web');
        $this->assertTrue($role->hasPermissionTo('test-custom-feature'));
        $this->assertFalse($role->hasPermissionTo('access-ebmis-modules'));

        $user = User::factory()->create(['user_type' => 'branch', 'status' => 'active']);
        $user->assignRole($role);
        $this->actingAs($user)->get(route('admin.modules.dashboard'))->assertForbidden();
    }

    public function test_branch_users_cannot_create_roles_or_assign_roles_to_users(): void
    {
        $user = User::factory()->create(['user_type' => 'branch', 'status' => 'active']);

        $this->actingAs($user)->post(route('admin.roles.store'), [
            'name' => 'Unauthorized Role',
            'permissions' => ['manage-investments'],
        ])->assertForbidden();

        $this->post(route('admin.users.store'), [
            'name' => 'Unauthorized User',
            'email' => 'unauthorized@example.test',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'user_type' => 'branch',
        ])->assertForbidden();

        $this->assertDatabaseMissing('roles', ['name' => 'Unauthorized Role']);
        $this->assertDatabaseMissing('users', ['email' => 'unauthorized@example.test']);
    }
}
