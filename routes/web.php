

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SchoolRegistrationController;

Route::get('/', function () {
    return view('auth.login');
});

// School Registration Routes (Public)
Route::get('/school/register', [SchoolRegistrationController::class, 'show'])->name('school.register');
Route::post('/school/register', [SchoolRegistrationController::class, 'store'])->name('school.register.store');
Route::get('/school/assessment', [SchoolRegistrationController::class, 'showAssessment'])->name('school.assessment');
Route::post('/school/assessment', [SchoolRegistrationController::class, 'storeAssessment'])->name('school.assessment.store');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        // Redirect Super Admin to admin dashboard
        if ($user->hasRole('Super Administrator') || $user->hasRole('superadmin')) {
            return redirect()->route('admin.home');
        }
        
        // Redirect School users to school dashboard
        if ($user->user_type === 'school' && $user->school) {
            return redirect()->route('school.dashboard');
        }
        
        // Default dashboard for other users
        return view('dashboard');
    })->name('dashboard');

    Route::get('/admin/home', [\App\Http\Controllers\AdminController::class, 'home'])->name('admin.home');
    
    // School Dashboard Route
    Route::get('/school/dashboard', [\App\Http\Controllers\School\SchoolDashboardController::class, 'index'])
        ->name('school.dashboard');
});

// Admin user management routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/admin/users/{user}/edit', [\App\Http\Controllers\AdminController::class, 'editUser'])->name('admin.users.edit');
});

// Access Control Routes (Super Admin only)
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'super_admin'
])->prefix('admin')->name('admin.')->group(function () {
    // Access Control Dashboard
    Route::get('/access-control', [\App\Http\Controllers\Admin\AccessControlController::class, 'index'])->name('access-control.index');
    
    // School Management Routes
    Route::resource('schools', \App\Http\Controllers\Admin\SchoolsController::class);
    Route::post('/schools/{school}/approve', [\App\Http\Controllers\Admin\SchoolsController::class, 'approve'])->name('schools.approve');
    Route::post('/schools/{school}/reject', [\App\Http\Controllers\Admin\SchoolsController::class, 'reject'])->name('schools.reject');
    Route::post('/schools/{school}/suspend', [\App\Http\Controllers\Admin\SchoolsController::class, 'suspend'])->name('schools.suspend');
    
    // User Management
    Route::get('/users', [\App\Http\Controllers\Admin\AccessControlController::class, 'users'])->name('users.index');
    Route::get('/users/create', [\App\Http\Controllers\Admin\AccessControlController::class, 'createUser'])->name('users.create');
    Route::post('/users', [\App\Http\Controllers\Admin\AccessControlController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\AccessControlController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [\App\Http\Controllers\Admin\AccessControlController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [\App\Http\Controllers\Admin\AccessControlController::class, 'deleteUser'])->name('users.delete');
    
    // Role Management
    Route::get('/roles', [\App\Http\Controllers\Admin\AccessControlController::class, 'roles'])->name('roles.index');
    Route::get('/roles/create', [\App\Http\Controllers\Admin\AccessControlController::class, 'createRole'])->name('roles.create');
    Route::post('/roles', [\App\Http\Controllers\Admin\AccessControlController::class, 'storeRole'])->name('roles.store');
    Route::get('/roles/{role}/edit', [\App\Http\Controllers\Admin\AccessControlController::class, 'editRole'])->name('roles.edit');
    Route::put('/roles/{role}', [\App\Http\Controllers\Admin\AccessControlController::class, 'updateRole'])->name('roles.update');
    Route::delete('/roles/{role}', [\App\Http\Controllers\Admin\AccessControlController::class, 'deleteRole'])->name('roles.delete');
    
    // Permission Management
    Route::get('/permissions', [\App\Http\Controllers\Admin\AccessControlController::class, 'permissions'])->name('permissions.index');
    Route::get('/permissions/create', [\App\Http\Controllers\Admin\AccessControlController::class, 'createPermission'])->name('permissions.create');
    Route::post('/permissions', [\App\Http\Controllers\Admin\AccessControlController::class, 'storePermission'])->name('permissions.store');
    Route::delete('/permissions/{permission}', [\App\Http\Controllers\Admin\AccessControlController::class, 'deletePermission'])->name('permissions.delete');
});

// School Dashboard Routes (For approved schools)
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'approved_school', // ✅ NEW: Ensures only approved schools can access
])->prefix('school')->name('school.')->group(function () {
    // Dashboard (approval check is in controller for better UX)
    Route::get('/dashboard', [\App\Http\Controllers\School\SchoolDashboardController::class, 'index'])->name('dashboard')->withoutMiddleware('approved_school');
    
    // Classes Management
    Route::resource('classes', \App\Http\Controllers\School\ClassesController::class);
    
    // Students Management
    Route::resource('students', \App\Http\Controllers\School\StudentsController::class);
    Route::post('/students/import', [\App\Http\Controllers\School\StudentsController::class, 'import'])->name('students.import');
    Route::get('/students/export/template', [\App\Http\Controllers\School\StudentsController::class, 'downloadTemplate'])->name('students.template');
    
    // Staff Management
    Route::resource('staff', \App\Http\Controllers\School\StaffController::class);
});


