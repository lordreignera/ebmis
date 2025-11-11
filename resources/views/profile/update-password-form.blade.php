<form wire:submit="updatePassword">
    <div class="form-body">
        <!-- Current Password -->
        <div class="form-group">
            <label for="current_password" class="form-label">
                <i class="mdi mdi-lock text-warning"></i> Current Password
            </label>
            <input type="password" 
                   class="form-control @error('current_password') is-invalid @enderror" 
                   id="current_password" 
                   wire:model="state.current_password" 
                   autocomplete="current-password"
                   placeholder="Enter your current password">
            @error('current_password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="form-text text-muted">
                For imported users, this is: <code>123456789</code>
            </small>
        </div>

        <!-- New Password -->
        <div class="form-group">
            <label for="password" class="form-label">
                <i class="mdi mdi-lock-plus text-success"></i> New Password
            </label>
            <input type="password" 
                   class="form-control @error('password') is-invalid @enderror" 
                   id="password" 
                   wire:model="state.password" 
                   autocomplete="new-password"
                   placeholder="Enter your new password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="form-text text-muted">
                <i class="mdi mdi-information"></i> Use at least 8 characters with letters and numbers
            </small>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
            <label for="password_confirmation" class="form-label">
                <i class="mdi mdi-lock-check text-success"></i> Confirm New Password
            </label>
            <input type="password" 
                   class="form-control @error('password_confirmation') is-invalid @enderror" 
                   id="password_confirmation" 
                   wire:model="state.password_confirmation" 
                   autocomplete="new-password"
                   placeholder="Confirm your new password">
            @error('password_confirmation')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Save Button -->
        <div class="form-group">
            @if (session('saved'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-circle"></i> Password updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <button type="submit" 
                    class="btn btn-gradient-warning btn-lg" 
                    wire:loading.attr="disabled">
                <i class="mdi mdi-lock-reset"></i>
                <span wire:loading.remove>Update Password</span>
                <span wire:loading>
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Updating...
                </span>
            </button>
        </div>
    </div>
</form>
