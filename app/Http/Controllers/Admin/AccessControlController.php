<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EbmisPermissionRegistry;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class AccessControlController extends Controller
{
    // Note: Security is handled by 'super_admin' middleware in routes
    
    // ===============================================================
    // MAIN DASHBOARD
    // ===============================================================
    
    public function index()
    {
        return view('admin.access-control.index');
    }

    // ===============================================================
    // ROLES MANAGEMENT
    // ===============================================================
    
    public function roles()
    {
        $roles = Role::with('permissions')->get()->map(function($role) {
            $role->users_count = $role->users()->count();
            return $role;
        });
        return view('admin.access-control.roles.index', compact('roles'));
    }

    public function createRole()
    {
        $permissions = $this->groupedPermissions();
        
        return view('admin.access-control.roles.create', compact('permissions'));
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create(['name' => $request->name]);
        
        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully!');
    }

    public function editRole(Role $role)
    {
        $permissions = $this->groupedPermissions();
        
        return view('admin.access-control.roles.edit', compact('role', 'permissions'));
    }

    public function updateRole(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($role->name === 'Super Administrator') {
            $role->syncPermissions(Permission::all());

            return redirect()->route('admin.roles.index')->with('success', 'Super Administrator retains all permissions.');
        }

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully!');
    }

    public function deleteRole(Role $role)
    {
        if ($role->name === 'Super Administrator') {
            return back()->with('error', 'Cannot delete Super Administrator role!');
        }

        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully!');
    }

    // ===============================================================
    // PERMISSIONS MANAGEMENT
    // ===============================================================
    
    public function permissions()
    {
        $permissions = $this->groupedPermissions(withRoles: true);
        
        return view('admin.access-control.permissions.index', compact('permissions'));
    }

    public function createPermission()
    {
        return view('admin.access-control.permissions.create');
    }

    public function storePermission(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
        ]);

        Permission::create(['name' => $request->name]);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission created successfully!');
    }

    public function deletePermission(Permission $permission)
    {
        if (EbmisPermissionRegistry::isRouteControlled($permission->name)) {
            return back()->with('error', 'This permission protects an EBIMS route and cannot be deleted. Remove it from roles instead.');
        }

        $permission->delete();
        return redirect()->route('admin.permissions.index')->with('success', 'Permission deleted successfully!');
    }

    // ===============================================================
    // USERS MANAGEMENT
    // ===============================================================
    
    public function users(Request $request)
    {
        $query = User::with('roles', 'school', 'branch');
        
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
        $roles = Role::with('permissions')->withCount('permissions')->orderBy('name')->get();
        $branches = \App\Models\Branch::active()->orderBy('name')->get();
        return view('admin.access-control.users.create', compact('roles', 'branches'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'user_type' => 'required|in:super_admin,school,branch',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'designation' => 'nullable|string|max:100',
            'branch_id' => 'required_if:user_type,branch|nullable|exists:branches,id',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => $request->user_type,
            'phone' => $request->phone,
            'address' => $request->address,
            'designation' => $request->designation,
            'branch_id' => $request->user_type === 'branch' ? $request->branch_id : null,
            'status' => 'active', // Super admin created users are active by default
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        if ($request->roles) {
            $user->assignRole($request->roles);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully!');
    }

    public function editUser(User $user)
    {
        $roles = Role::with('permissions')->withCount('permissions')->orderBy('name')->get();
        $branches = \App\Models\Branch::active()->orderBy('name')->get();
        return view('admin.access-control.users.edit', compact('user', 'roles', 'branches'));
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'user_type' => 'required|in:super_admin,school,branch',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'designation' => 'nullable|string|max:100',
            'branch_id' => 'required_if:user_type,branch|nullable|exists:branches,id',
            'status' => 'required|in:pending,active,suspended,rejected',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'branch_id' => $request->user_type === 'branch' ? $request->branch_id : null,
            'user_type' => $request->user_type,
            'phone' => $request->phone,
            'address' => $request->address,
            'designation' => $request->designation,
            'status' => $request->status,
        ]);

        if ($request->password) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles($request->roles ?? []);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully!');
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot delete your own account!');
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully!');
    }

    private function groupedPermissions(bool $withRoles = false)
    {
        $query = Permission::query();

        if ($withRoles) {
            $query->with('roles');
        }

        return EbmisPermissionRegistry::groupedPermissions($query->get());
    }
}
