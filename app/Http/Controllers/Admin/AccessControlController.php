<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use App\Models\School;
use App\Models\User;
use App\Support\EbmisPermissionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class AccessControlController extends Controller
{
    // Note: Security is handled by 'super_admin' middleware in routes
    private const GUARD = 'web';
    
    // ===============================================================
    // MAIN DASHBOARD
    // ===============================================================
    
    public function index()
    {
        $pendingInvestorApprovals = Investor::query()
            ->where('portal_status', 'pending')
            ->where('soft_delete', 0)
            ->count();

        return view('admin.access-control.index', compact('pendingInvestorApprovals'));
    }

    // ===============================================================
    // ROLES MANAGEMENT
    // ===============================================================
    
    public function roles()
    {
        $assignmentCounts = $this->roleAssignmentCounts();

        $roles = Role::with('permissions')
            ->where('guard_name', self::GUARD)
            ->orderBy('name')
            ->get()
            ->map(function($role) use ($assignmentCounts) {
                $role->users_count = (int) ($assignmentCounts[$role->getKey()] ?? 0);
                return $role;
            });

        return view('admin.access-control.roles.index', compact('roles'));
    }

    public function createRole()
    {
        return view('admin.access-control.roles.create');
    }

    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', self::GUARD),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', self::GUARD),
            ],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $role = Role::create([
                    'name' => $validated['name'],
                    'guard_name' => self::GUARD,
                ]);

                $role->syncPermissions($this->permissionModels(
                    EbmisPermissionRegistry::rolePermissionsWithWorkspace($validated['permissions'] ?? [])
                ));
            });

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return redirect()->route('admin.roles.index')->with('success', 'Role created successfully. Open the Permission Matrix to assign access.');
        } catch (\Throwable $e) {
            Log::error('Access control role creation failed', [
                'name' => $validated['name'] ?? $request->input('name'),
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Role could not be created: ' . $e->getMessage());
        }
    }

    public function editRole(Role $role)
    {
        $role->load('permissions');
        $role->users_count = $this->roleAssignmentCount($role);
        
        return view('admin.access-control.roles.edit', compact('role'));
    }

    public function updateRole(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', self::GUARD)->ignore($role->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', self::GUARD),
            ],
        ]);

        try {
            if ($role->name === 'Super Administrator') {
                $role->syncPermissions(Permission::where('guard_name', self::GUARD)->get());
                app(PermissionRegistrar::class)->forgetCachedPermissions();

                return redirect()->route('admin.roles.index')->with('success', 'Super Administrator retains all permissions.');
            }

            DB::transaction(function () use ($role, $validated) {
                $role->update([
                    'name' => $validated['name'],
                    'guard_name' => self::GUARD,
                ]);

                // Permission assignment is normally handled by the permission
                // matrix. Keep accepting a permissions array for existing clients
                // and older forms that still submit one.
                if (array_key_exists('permissions', $validated)) {
                    $role->syncPermissions($this->permissionModels(
                        EbmisPermissionRegistry::rolePermissionsWithWorkspace($validated['permissions'] ?? [])
                    ));
                }
            });

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully!');
        } catch (\Throwable $e) {
            Log::error('Access control role update failed', [
                'role_id' => $role->id,
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Role could not be updated: ' . $e->getMessage());
        }
    }

    public function deleteRole(Role $role)
    {
        if ($role->name === 'Super Administrator') {
            return back()->with('error', 'Cannot delete Super Administrator role!');
        }

        try {
            $role->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully!');
        } catch (\Throwable $e) {
            Log::error('Access control role deletion failed', [
                'role_id' => $role->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Role could not be deleted: ' . $e->getMessage());
        }
    }

    // ===============================================================
    // PERMISSIONS MANAGEMENT
    // ===============================================================
    
    public function permissions(Request $request)
    {
        $permissionQuery = Permission::where('guard_name', self::GUARD)
            ->with(['roles' => fn ($roles) => $roles->where('guard_name', self::GUARD)->orderBy('name')]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $permissionQuery->where('name', 'like', '%' . $search . '%');
        }

        $permissionsPage = $permissionQuery
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $flatPermissions = $permissionsPage->getCollection()->values();
        $permissions = EbmisPermissionRegistry::groupedPermissions($flatPermissions);

        $allPermissions = Permission::where('guard_name', self::GUARD)
            ->with(['roles' => fn ($roles) => $roles->where('guard_name', self::GUARD)])
            ->get();
        $allGroupedPermissions = EbmisPermissionRegistry::groupedPermissions($allPermissions);
        $permissionsTotal = $allPermissions->count();
        $permissionsInUseTotal = $allPermissions->filter(fn ($permission) => $permission->roles->isNotEmpty())->count();
        $permissionsUnusedTotal = $permissionsTotal - $permissionsInUseTotal;
        $categoriesTotal = $allGroupedPermissions->count();
        $roles = Role::with('permissions')
            ->where('guard_name', self::GUARD)
            ->orderByRaw("CASE WHEN LOWER(name) IN ('super administrator', 'superadmin') THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
        $permissionAssignments = $this->permissionAssignments($flatPermissions, $roles);
        $administratorRole = $roles->first(fn ($role) => in_array(strtolower($role->name), ['super administrator', 'superadmin'], true));
        $activeRole = $roles->firstWhere('id', (int) $request->input('role')) ?: $roles->first();
        $activeRoleId = optional($activeRole)->id;
        $assignedPermissionNames = optional($activeRole?->permissions)->pluck('name')->all() ?? [];
        
        return view('admin.access-control.permissions.index', compact(
            'permissions',
            'flatPermissions',
            'permissionsPage',
            'permissionsTotal',
            'permissionsInUseTotal',
            'permissionsUnusedTotal',
            'categoriesTotal',
            'roles',
            'permissionAssignments',
            'administratorRole',
            'activeRole',
            'activeRoleId',
            'assignedPermissionNames'
        ));
    }

    public function saveRolePermissions(Request $request)
    {
        $validated = $request->validate([
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where('guard_name', self::GUARD),
            ],
            'permission_ids' => ['required', 'array', 'min:1'],
            'permission_ids.*' => [
                'integer',
                Rule::exists('permissions', 'id')->where('guard_name', self::GUARD),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'integer',
                Rule::exists('permissions', 'id')->where('guard_name', self::GUARD),
            ],
        ]);

        $role = Role::where('guard_name', self::GUARD)->findOrFail($validated['role_id']);
        $permissionIds = collect($validated['permission_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $selectedPermissionIds = collect($validated['permissions'] ?? [])->map(fn ($id) => (int) $id)->unique();
        $permissions = Permission::where('guard_name', self::GUARD)
            ->whereIn('id', $permissionIds)
            ->get()
            ->keyBy('id');

        try {
            DB::transaction(function () use ($role, $permissionIds, $selectedPermissionIds, $permissions) {
                $isAdministrator = in_array(strtolower($role->name), ['super administrator', 'superadmin'], true);

                foreach ($permissionIds as $permissionId) {
                    $permission = $permissions->get($permissionId);

                    if (! $permission) {
                        continue;
                    }

                    if ($isAdministrator || $selectedPermissionIds->contains($permissionId)) {
                        $role->givePermissionTo($permission);
                    } else {
                        $role->revokePermissionTo($permission);
                    }
                }

                $role->load('permissions');
                $workspacePermission = Permission::where('guard_name', self::GUARD)
                    ->where('name', 'access-ebmis-modules')
                    ->first();

                if ($workspacePermission && $role->permissions->contains(fn ($permission) => EbmisPermissionRegistry::isRouteControlled($permission->name))) {
                    $role->givePermissionTo($workspacePermission);
                } elseif ($workspacePermission && $permissions->contains('id', $workspacePermission->id)) {
                    $role->revokePermissionTo($workspacePermission);
                }
            });

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return redirect()->route('admin.permissions.index', array_filter([
                'role' => $role->id,
                'page' => $request->input('page'),
                'search' => $request->input('search'),
            ]))->with('success', 'Permissions saved for ' . $role->name . '.');
        } catch (\Throwable $e) {
            Log::error('Access control role permission update failed', [
                'role_id' => $role->id,
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Permissions could not be saved: ' . $e->getMessage());
        }
    }

    public function savePermissionMatrix(Request $request)
    {
        $validated = $request->validate([
            'permission_ids' => ['required', 'array', 'min:1'],
            'permission_ids.*' => [
                'integer',
                Rule::exists('permissions', 'id')->where('guard_name', self::GUARD),
            ],
            'permissionRoles' => ['nullable', 'array'],
            'permissionRoles.*' => ['nullable', 'array'],
        ]);

        $permissionIds = collect($validated['permission_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $permissions = Permission::where('guard_name', self::GUARD)
            ->whereIn('id', $permissionIds)
            ->get()
            ->keyBy('id');
        $roles = Role::where('guard_name', self::GUARD)->get()->keyBy('id');
        $assignments = $validated['permissionRoles'] ?? [];

        try {
            DB::transaction(function () use ($permissionIds, $permissions, $roles, $assignments) {
                foreach ($permissionIds as $permissionId) {
                    $permission = $permissions->get($permissionId);

                    if (! $permission) {
                        continue;
                    }

                    foreach ($roles as $role) {
                        $isAdministrator = in_array(strtolower($role->name), ['super administrator', 'superadmin'], true);
                        $isSelected = ! empty($assignments[$permissionId][$role->id] ?? null);

                        if ($isAdministrator || $isSelected) {
                            $role->givePermissionTo($permission);
                        } else {
                            $role->revokePermissionTo($permission);
                        }
                    }
                }

                // A role needs workspace access before any route-controlled module
                // permission can take effect. Keep this in sync with role creation
                // and editing without touching permissions on other matrix pages.
                foreach ($roles as $role) {
                    $role->load('permissions');

                    if ($role->permissions->contains(fn ($permission) => EbmisPermissionRegistry::isRouteControlled($permission->name))) {
                        $workspacePermission = Permission::where('guard_name', self::GUARD)
                            ->where('name', 'access-ebmis-modules')
                            ->first();

                        if ($workspacePermission) {
                            $role->givePermissionTo($workspacePermission);
                        }
                    }
                }
            });

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return redirect()->route('admin.permissions.index', array_filter([
                'page' => $request->input('page'),
                'search' => $request->input('search'),
                'role' => $request->input('role'),
            ]))->with('success', 'Permission assignments saved successfully.');
        } catch (\Throwable $e) {
            Log::error('Access control permission matrix update failed', [
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Permission assignments could not be saved: ' . $e->getMessage());
        }
    }

    public function createPermission()
    {
        return view('admin.access-control.permissions.create');
    }

    public function storePermission(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('permissions', 'name')->where('guard_name', self::GUARD),
            ],
        ]);

        try {
            Permission::create([
                'name' => $validated['name'],
                'guard_name' => self::GUARD,
            ]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return redirect()->route('admin.permissions.index')->with('success', 'Permission created successfully!');
        } catch (\Throwable $e) {
            Log::error('Access control permission creation failed', [
                'name' => $validated['name'] ?? $request->input('name'),
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Permission could not be created: ' . $e->getMessage());
        }
    }

    public function deletePermission(Permission $permission)
    {
        if (EbmisPermissionRegistry::isRouteControlled($permission->name)) {
            return back()->with('error', 'This permission protects an EBIMS route and cannot be deleted. Remove it from roles instead.');
        }

        try {
            $permission->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return redirect()->route('admin.permissions.index')->with('success', 'Permission deleted successfully!');
        } catch (\Throwable $e) {
            Log::error('Access control permission deletion failed', [
                'permission_id' => $permission->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Permission could not be deleted: ' . $e->getMessage());
        }
    }

    // ===============================================================
    // USERS MANAGEMENT
    // ===============================================================
    
    public function users(Request $request)
    {
        $query = User::with('roles', 'school', 'branch', 'branches');
        
        // Apply filters
        if ($request->filter) {
            switch ($request->filter) {
                case 'pending':
                    $query->where('status', 'pending');
                    break;
                case 'active':
                    $query->where('status', 'active');
                    break;
                case 'super_admin':
                    $query->where('user_type', 'super_admin');
                    break;
                case 'branch':
                    $query->where('user_type', 'branch');
                    break;
                case 'school':
                    $query->where('user_type', 'school');
                    break;
            }
        }
        
        // Apply search
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        $users = $query->latest()->paginate(20)->withQueryString();
        
        return view('admin.access-control.users.index', compact('users'));
    }

    public function createUser()
    {
        $roles = Role::with('permissions')
            ->withCount('permissions')
            ->where('guard_name', self::GUARD)
            ->orderBy('name')
            ->get();
        $branches = \App\Models\Branch::active()->orderBy('name')->get();
        $schools = School::where('status', 'approved')->orderBy('school_name')->get();
        return view('admin.access-control.users.create', compact('roles', 'branches', 'schools'));
    }

    public function storeUser(Request $request)
    {
        // Accept the legacy single branch field from older clients while the
        // user interface now submits branch_ids[] for multi-branch access.
        if ($request->input('user_type') === 'branch'
            && !$request->filled('branch_ids')
            && $request->filled('branch_id')) {
            $request->merge(['branch_ids' => [$request->input('branch_id')]]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'user_type' => 'required|in:super_admin,school,branch',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'designation' => 'nullable|string|max:100',
            'branch_id' => 'nullable|exists:branches,id',
            'branch_ids' => 'required_if:user_type,branch|array|min:1',
            'branch_ids.*' => 'integer|distinct|exists:branches,id',
            'school_id' => [
                'required_if:user_type,school',
                'nullable',
                Rule::exists('schools', 'id')->where('status', 'approved'),
            ],
            'roles' => 'nullable|array',
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name')->where('guard_name', self::GUARD),
            ],
        ]);

        $branchIds = $validated['user_type'] === 'branch'
            ? array_values(array_unique(array_map('intval', $validated['branch_ids'] ?? [])))
            : [];

        try {
            DB::transaction(function () use ($validated, $request, $branchIds) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'email_verified_at' => now(),
                    'password' => Hash::make($validated['password']),
                    'user_type' => $validated['user_type'],
                    'phone' => $validated['phone'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'designation' => $validated['designation'] ?? null,
                    'branch_id' => $branchIds[0] ?? null,
                    'school_id' => $validated['user_type'] === 'school' ? ($validated['school_id'] ?? null) : null,
                    'status' => 'active',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                $user->branches()->sync($branchIds);
                $user->syncRoles($this->roleModels($validated['roles'] ?? []));
            });

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return redirect()->route('admin.users.index')->with('success', 'User created successfully and can log in with the assigned access.');
        } catch (\Throwable $e) {
            Log::error('Access control user creation failed', [
                'email' => $request->input('email'),
                'message' => $e->getMessage(),
            ]);

            return back()->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'User could not be created: ' . $e->getMessage());
        }
    }

    public function editUser(User $user)
    {
        $user->load('branches');
        $roles = Role::with('permissions')
            ->withCount('permissions')
            ->where('guard_name', self::GUARD)
            ->orderBy('name')
            ->get();
        $branches = \App\Models\Branch::active()->orderBy('name')->get();
        $schools = School::where('status', 'approved')->orderBy('school_name')->get();
        return view('admin.access-control.users.edit', compact('user', 'roles', 'branches', 'schools'));
    }

    public function updateUser(Request $request, User $user)
    {
        // Accept the legacy single branch field from older clients while the
        // user interface now submits branch_ids[] for multi-branch access.
        if ($request->input('user_type') === 'branch'
            && !$request->filled('branch_ids')
            && $request->filled('branch_id')) {
            $request->merge(['branch_ids' => [$request->input('branch_id')]]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'user_type' => 'required|in:super_admin,school,branch',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'designation' => 'nullable|string|max:100',
            'branch_id' => 'nullable|exists:branches,id',
            'branch_ids' => 'required_if:user_type,branch|array|min:1',
            'branch_ids.*' => 'integer|distinct|exists:branches,id',
            'school_id' => [
                'required_if:user_type,school',
                'nullable',
                Rule::exists('schools', 'id')->where('status', 'approved'),
            ],
            'status' => 'required|in:pending,active,suspended,rejected',
            'roles' => 'nullable|array',
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name')->where('guard_name', self::GUARD),
            ],
        ]);

        $branchIds = $validated['user_type'] === 'branch'
            ? array_values(array_unique(array_map('intval', $validated['branch_ids'] ?? [])))
            : [];

        try {
            DB::transaction(function () use ($user, $validated, $branchIds) {
                $user->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'branch_id' => $branchIds[0] ?? null,
                    'school_id' => $validated['user_type'] === 'school' ? ($validated['school_id'] ?? null) : null,
                    'user_type' => $validated['user_type'],
                    'phone' => $validated['phone'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'designation' => $validated['designation'] ?? null,
                    'status' => $validated['status'],
                ]);

                $user->branches()->sync($branchIds);

                if (!empty($validated['password'])) {
                    $user->update([
                        'password' => Hash::make($validated['password']),
                        'email_verified_at' => $user->email_verified_at ?: now(),
                    ]);
                }

                $user->syncRoles($this->roleModels($validated['roles'] ?? []));
            });

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return redirect()->route('admin.users.index')->with('success', 'User updated successfully!');
        } catch (\Throwable $e) {
            Log::error('Access control user update failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return back()->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'User could not be updated: ' . $e->getMessage());
        }
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot delete your own account!');
        }

        try {
            $user->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return redirect()->route('admin.users.index')->with('success', 'User deleted successfully!');
        } catch (\Throwable $e) {
            Log::error('Access control user deletion failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'User could not be deleted: ' . $e->getMessage());
        }
    }

    private function groupedPermissions(bool $withRoles = false)
    {
        $query = Permission::where('guard_name', self::GUARD);

        if ($withRoles) {
            $query->with(['roles' => fn ($roles) => $roles->where('guard_name', self::GUARD)->orderBy('name')]);
        }

        return EbmisPermissionRegistry::groupedPermissions($query->get());
    }

    private function permissionAssignments($permissions, $roles): array
    {
        if ($permissions->isEmpty() || $roles->isEmpty()) {
            return [];
        }

        $permissionTable = config('permission.table_names.permissions', 'permissions');
        $roleTable = config('permission.table_names.roles', 'roles');
        $pivotTable = config('permission.table_names.role_has_permissions', 'role_has_permissions');
        $permissionPivot = config('permission.column_names.permission_pivot_key') ?: 'permission_id';
        $rolePivot = config('permission.column_names.role_pivot_key') ?: 'role_id';

        return DB::table($pivotTable . ' as rhp')
            ->join($permissionTable . ' as p', 'p.id', '=', 'rhp.' . $permissionPivot)
            ->join($roleTable . ' as r', 'r.id', '=', 'rhp.' . $rolePivot)
            ->where('p.guard_name', self::GUARD)
            ->where('r.guard_name', self::GUARD)
            ->whereIn('p.name', $permissions->pluck('name')->all())
            ->whereIn('r.name', $roles->pluck('name')->all())
            ->select('p.name as permission_name', 'r.name as role_name')
            ->get()
            ->reduce(function (array $assignments, $row) {
                $assignments[$row->permission_name][$row->role_name] = true;

                return $assignments;
            }, []);
    }

    private function permissionModels(array $permissionNames)
    {
        if (empty($permissionNames)) {
            return collect();
        }

        return Permission::where('guard_name', self::GUARD)
            ->whereIn('name', array_values(array_unique($permissionNames)))
            ->get();
    }

    private function roleModels(array $roleNames)
    {
        if (empty($roleNames)) {
            return collect();
        }

        return Role::where('guard_name', self::GUARD)
            ->whereIn('name', array_values(array_unique($roleNames)))
            ->get();
    }

    private function roleAssignmentCounts()
    {
        $table = config('permission.table_names.model_has_roles', 'model_has_roles');
        $pivotRole = config('permission.column_names.role_pivot_key') ?: 'role_id';

        return DB::table($table)
            ->select($pivotRole, DB::raw('COUNT(*) as aggregate'))
            ->groupBy($pivotRole)
            ->pluck('aggregate', $pivotRole);
    }

    private function roleAssignmentCount(Role $role): int
    {
        $table = config('permission.table_names.model_has_roles', 'model_has_roles');
        $pivotRole = config('permission.column_names.role_pivot_key') ?: 'role_id';

        return (int) DB::table($table)
            ->where($pivotRole, $role->getKey())
            ->count();
    }
}
