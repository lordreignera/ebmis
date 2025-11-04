<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;

class SchoolsController extends Controller
{
    /**
     * Display a listing of schools.
     */
    public function index()
    {
        $schools = School::with('approvedBy')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.schools.index', compact('schools'));
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
