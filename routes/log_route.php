Route::get('/view-logs', function() {
    // Only allow Super Admin and Branch Managers
    if (!auth()->check() || 
        !(auth()->user()->hasRole('Super Administrator') || 
          auth()->user()->hasRole('Branch Manager'))) {
        abort(403, 'Unauthorized');
    }
    
    return view('admin.logs.viewer');
})->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->name('admin.logs.viewer');
