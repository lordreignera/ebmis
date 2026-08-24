<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessControlControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_control_pages_render_for_super_admin(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin']);
        $role = Role::create(['name' => 'Test Branch Manager', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'test-view-members', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Test Branch Manager');

        $this->actingAs($admin)
            ->get(route('admin.roles.edit', $role))
            ->assertOk()
            ->assertSee('Edit Role: Test Branch Manager')
            ->assertSee('test-view-members');

        $this->actingAs($admin)
            ->get(route('admin.permissions.index'))
            ->assertOk()
            ->assertSee('All Permissions')
            ->assertSee('test-view-members');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($admin->email);
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
}
