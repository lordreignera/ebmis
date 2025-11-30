<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LogoutController extends Controller
{
    /**
     * Handle logout with success message
     */
    public function logout(Request $request)
    {
        // Log the user out
        Auth::guard('web')->logout();

        // Invalidate the session
        $request->session()->invalidate();

        // Regenerate the CSRF token
        $request->session()->regenerateToken();

        // Use query parameter to pass success message (will be displayed on login page)
        return redirect()->route('login', ['logout' => 'success']);
    }
}
