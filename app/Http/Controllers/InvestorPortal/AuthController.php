<?php

namespace App\Http\Controllers\InvestorPortal;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Investor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('investor')->check()) {
            return redirect()->route('investor.dashboard');
        }

        return view('investor.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['email'] = strtolower(trim($credentials['email']));

        $investor = Investor::whereRaw('LOWER(email) = ?', [$credentials['email']])
            ->where('soft_delete', 0)
            ->first();

        if (!$investor || !$investor->password || !Hash::check($credentials['password'], $investor->password)) {
            return back()->withErrors([
                'email' => 'The supplied investor login details are incorrect.',
            ])->onlyInput('email');
        }

        if ($investor->portal_status === 'pending' || !$investor->portal_status) {
            return back()->with('warning', 'Your registration is awaiting approval by the EBIMS Super Administrator.')
                ->onlyInput('email');
        }

        if ($investor->portal_status === 'rejected') {
            return back()->with('error', 'Your investor registration was not approved. Please contact EBIMS support for assistance.')
                ->onlyInput('email');
        }

        if (!$investor->can_access_portal) {
            return back()->with('error', 'Your investor portal account is currently inactive.')
                ->onlyInput('email');
        }

        Auth::guard('investor')->login($investor, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('investor.dashboard'));
    }

    public function showRegistration(): View|RedirectResponse
    {
        if (Auth::guard('investor')->check()) {
            return redirect()->route('investor.dashboard');
        }

        return view('investor.auth.register', [
            'countries' => Country::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function registrationStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status_email' => ['nullable', 'email', 'max:255', 'required_without:status_IDnumber'],
            'status_IDnumber' => ['nullable', 'string', 'max:100', 'required_without:status_email'],
        ]);

        $email = isset($validated['status_email']) ? strtolower(trim($validated['status_email'])) : null;
        $idNumber = isset($validated['status_IDnumber']) ? trim($validated['status_IDnumber']) : null;
        $investor = $this->findExistingRegistration($email, $idNumber);

        if (! $investor) {
            return redirect()->route('investor.register')->withInput()->with('registration_status', [
                'status' => 'not_found',
                'tone' => 'info',
                'title' => 'No registration found',
                'message' => 'No active investor registration matched the information provided. You may continue with a new registration.',
            ]);
        }

        return redirect()->route('investor.register')->withInput()->with('registration_status', $this->registrationStatusDetails($investor));
    }

    public function register(Request $request): RedirectResponse
    {
        if ($request->filled('email')) {
            $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        }

        if ($request->filled('IDnumber')) {
            $request->merge(['IDnumber' => trim((string) $request->input('IDnumber'))]);
        }

        $existingRegistration = $this->findExistingRegistration(
            $request->input('email'),
            $request->input('IDnumber')
        );

        if ($existingRegistration) {
            $statusDetails = $this->registrationStatusDetails($existingRegistration);
            $field = $request->filled('email') && strcasecmp((string) $existingRegistration->email, (string) $request->input('email')) === 0
                ? 'email'
                : 'IDnumber';

            return redirect()->route('investor.register')
                ->withInput($request->except(['password', 'password_confirmation', 'id_document', 'profile_photo']))
                ->withErrors([$field => $statusDetails['message']])
                ->with('registration_status', $statusDetails);
        }

        $validated = $request->validate([
            'title' => ['required', 'integer', 'in:1,2,3,4,5'],
            'fname' => ['required', 'string', 'max:200'],
            'lname' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:255', 'unique:investors,email'],
            'phone' => ['required', 'string', 'max:30'],
            'gender' => ['required', 'in:Male,Female'],
            'dob' => ['required', 'date', 'before:today'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:200'],
            'country' => ['required', 'integer', 'exists:countries,id'],
            'zip' => ['nullable', 'string', 'max:30'],
            'IDtype' => ['required', 'string', 'max:100'],
            'IDnumber' => [
                'required',
                'string',
                'max:100',
                Rule::unique('investors', 'IDnumber'),
            ],
            'id_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'terms' => ['accepted'],
        ]);

        $investorId = null;

        try {
            DB::transaction(function () use ($request, $validated, &$investorId): void {
                $investor = Investor::create([
                    'title' => $validated['title'],
                    'fname' => $validated['fname'],
                    'lname' => $validated['lname'],
                    'address' => $validated['address'],
                    'city' => $validated['city'],
                    'country' => $validated['country'],
                    'zip' => $validated['zip'] ?? null,
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'passcode' => random_int(100000, 999999),
                    'phone' => $validated['phone'],
                    'gender' => $validated['gender'],
                    'IDtype' => $validated['IDtype'],
                    'IDnumber' => $validated['IDnumber'],
                    'dob' => $validated['dob'],
                    'status' => 0,
                    'portal_status' => 'pending',
                    'description' => '',
                    'soft_delete' => 0,
                ]);
                $investorId = $investor->id;

                $directory = 'investor-kyc/'.$investor->id;
                $investor->update([
                    'image_path' => $request->file('id_document')->store($directory, 'local'),
                    'photo_path' => $request->file('profile_photo')->store($directory, 'local'),
                ]);

            });
        } catch (\Throwable $exception) {
            if ($investorId) {
                Storage::disk('local')->deleteDirectory('investor-kyc/'.$investorId);
            }

            report($exception);

            return back()->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'We could not complete your investor registration. Please try again.');
        }

        return redirect()->route('investor.registration-submitted')
            ->with('registration_email', $validated['email']);
    }

    private function findExistingRegistration(?string $email, ?string $idNumber): ?Investor
    {
        $email = $email !== null ? strtolower(trim($email)) : null;
        $idNumber = $idNumber !== null ? trim($idNumber) : null;

        if (! $email && ! $idNumber) {
            return null;
        }

        return Investor::query()
            ->where(function ($query) use ($email, $idNumber): void {
                if ($email) {
                    $query->whereRaw('LOWER(email) = ?', [$email]);
                }

                if ($idNumber) {
                    $email ? $query->orWhere('IDnumber', $idNumber) : $query->where('IDnumber', $idNumber);
                }
            })
            ->orderByDesc('id')
            ->first();
    }

    private function registrationStatusDetails(Investor $investor): array
    {
        if ((int) $investor->soft_delete === 1 || ! $investor->portal_status) {
            return [
                'status' => 'existing',
                'tone' => 'warning',
                'title' => 'An existing investor record was found',
                'message' => 'This email or identification number is already attached to an EBIMS investor record. Please contact EBIMS support instead of submitting another registration.',
            ];
        }

        return match ($investor->portal_status) {
            'approved' => [
                'status' => 'approved',
                'tone' => 'success',
                'title' => 'Registration already approved',
                'message' => 'This investor registration is approved. Use the investor login page to access the portal.',
            ],
            'rejected' => [
                'status' => 'rejected',
                'tone' => 'error',
                'title' => 'Registration was rejected',
                'message' => 'This investor registration was rejected. Please contact EBIMS support before submitting any new information.',
            ],
            default => [
                'status' => 'pending',
                'tone' => 'warning',
                'title' => 'Registration is awaiting review',
                'message' => 'This investor registration has already been received and is awaiting Super Administrator approval. Do not submit it again.',
            ],
        };
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('investor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('investor.login')->with('success', 'You have signed out successfully.');
    }
}
