<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Register login view
        Fortify::loginView(function () {
            return view('auth.login');
        });

        // Register register view if needed
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // ===============================================================
        // CUSTOM AUTHENTICATION WITH APPROVAL STATUS VALIDATION
        // OPTIMIZED: Only select needed columns for faster login
        // ===============================================================
        Fortify::authenticateUsing(function (Request $request) {
            // PERFORMANCE: Select only essential columns, don't load relationships yet
            $user = User::select('id', 'name', 'email', 'password', 'status', 'user_type', 'school_id', 'branch_id')
                ->where('email', $request->email)
                ->first();

            if ($user && Hash::check($request->password, $user->password)) {
                // Fast approval check without loading relationships
                if ($user->user_type === 'school' && $user->school_id) {
                    // Only load school if needed for school users
                    $schoolStatus = \DB::table('schools')
                        ->where('id', $user->school_id)
                        ->value('status');
                    
                    if ($schoolStatus !== 'approved' || $user->status !== 'active') {
                        throw ValidationException::withMessages([
                            Fortify::username() => [$user->getStatusMessage()],
                        ]);
                    }
                } else {
                    // For non-school users, just check status
                    if ($user->status !== 'active') {
                        throw ValidationException::withMessages([
                            Fortify::username() => ['Your account is not active. Please contact the administrator.'],
                        ]);
                    }
                }

                return $user;
            }
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
