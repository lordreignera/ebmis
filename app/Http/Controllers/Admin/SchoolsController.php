<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolsController extends Controller
{
    /**
     * Display a listing of schools.
     */
    public function index(Request $request)
    {
        $schools = School::with('approvedBy')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.schools.index', compact('schools'));
    }

    /**
     * Show the concise administrator school-registration form.
     */
    public function create()
    {
        return view('admin.schools.create');
    }

    /**
     * Create a school and its initial school administrator account together.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'school_code' => ['nullable', 'string', 'max:50', 'unique:schools,school_code'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'school_type' => ['required', 'in:Primary,Secondary,Primary & Secondary,Nursery,University,College,Other'],
            'ownership' => ['required', 'in:Government,Private,Religious,Community,NGO'],
            'contact_person' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:191', 'unique:schools,email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'physical_address' => ['required', 'string'],
            'district' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'in:pending,approved'],
        ]);

        [$school, $user] = DB::transaction(function () use ($validated, $request) {
            $approved = $validated['status'] === 'approved';
            $passwordHash = Hash::make($validated['password']);

            $school = School::create([
                'school_name' => $validated['school_name'],
                'school_code' => ($validated['school_code'] ?? null) ?: $this->nextSchoolCode(),
                'registration_number' => $validated['registration_number'] ?? null,
                'school_type' => $validated['school_type'],
                'ownership' => $validated['ownership'],
                'contact_person' => $validated['contact_person'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'admin_password' => $passwordHash,
                'password_set_at' => now(),
                'physical_address' => $validated['physical_address'],
                'district' => $validated['district'],
                'status' => $validated['status'],
                'approved_by' => $approved ? $request->user()->id : null,
                'approved_at' => $approved ? now() : null,
                'approval_notes' => $approved ? 'Created and approved by administrator' : null,
            ]);

            $user = User::create([
                'name' => $validated['contact_person'],
                'email' => $validated['email'],
                'email_verified_at' => now(),
                'password' => $passwordHash,
                'user_type' => 'school',
                'school_id' => $school->id,
                'status' => $approved ? 'active' : 'pending',
                'approved_by' => $approved ? $request->user()->id : null,
                'approved_at' => $approved ? now() : null,
                'phone' => $validated['phone'],
            ]);

            return [$school, $user];
        });

        return redirect()->route('admin.schools.show', $school)
            ->with('success', "School and administrator account for {$user->email} created successfully.");
    }

    private function nextSchoolCode(): string
    {
        do {
            $code = 'SCH-' . strtoupper(Str::random(8));
        } while (School::where('school_code', $code)->exists());

        return $code;
    }

    /**
     * Display the specified school.
     */
    public function show(School $school)
    {
        $school->load('approvedBy', 'users');
        
        return view('admin.schools.show', compact('school'));
    }

    /**
     * Show the form for editing the specified school.
     */
    public function edit(School $school)
    {
        return view('admin.schools.edit', compact('school'));
    }

    /**
     * Update the specified school in storage.
     */
    public function update(Request $request, School $school)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'status' => 'required|in:pending,approved,suspended,rejected',
            'approval_notes' => 'nullable|string',
        ]);

        $school->update($validated);

        return redirect()->route('admin.schools.show', $school)
            ->with('success', 'School updated successfully!');
    }

    /**
     * Approve a school.
     */
    public function approve(School $school)
    {
        $school->approve(auth()->id(), 'Approved by administrator');
        
        // Update the associated user status
        User::where('school_id', $school->id)->update(['status' => 'active']);

        return redirect()->back()
            ->with('success', 'School approved successfully! Users can now login.');
    }

    /**
     * Reject a school.
     */
    public function reject(Request $request, School $school)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $school->reject(auth()->id(), $validated['rejection_reason']);
        
        // Update the associated user status
        User::where('school_id', $school->id)->update(['status' => 'rejected']);

        return redirect()->back()
            ->with('success', 'School rejected.');
    }

    /**
     * Suspend a school.
     */
    public function suspend(Request $request, School $school)
    {
        $validated = $request->validate([
            'suspension_reason' => 'required|string|max:500',
        ]);

        $school->update([
            'status' => 'suspended',
            'approval_notes' => $validated['suspension_reason'],
        ]);
        
        // Update the associated user status
        User::where('school_id', $school->id)->update(['status' => 'suspended']);

        return redirect()->back()
            ->with('success', 'School suspended.');
    }

    /**
     * Remove the specified school from storage.
     */
    public function destroy(School $school)
    {
        // Delete associated users
        User::where('school_id', $school->id)->delete();
        
        $school->delete();

        return redirect()->route('admin.schools.index')
            ->with('success', 'School deleted successfully!');
    }
}
