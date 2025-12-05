<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StaffController extends Controller
{
    use AuthorizesRequests;
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Staff::class);
        
        $school = auth()->user()->school;
        
        $query = Staff::where('school_id', $school->id);

        // Filter by staff type
        if ($request->filled('staff_type')) {
            $query->where('staff_type', $request->staff_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('staff_id', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        $staff = $query->orderBy('first_name')->paginate(20);

        return view('school.staff.index', compact('school', 'staff'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Staff::class);
        
        $school = auth()->user()->school;

        return view('school.staff.create', compact('school'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Staff::class);
        
        $school = auth()->user()->school;

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'other_names' => 'nullable|string|max:255',
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date',
            'nationality' => 'nullable|string|max:100',
            'national_id' => 'nullable|string|max:50',
            'religion' => 'nullable|string|max:100',
            'phone_number' => 'required|string|max:50',
            'email' => 'nullable|email|max:191',
            'address' => 'nullable|string',
            'district' => 'nullable|string|max:100',
            'next_of_kin_name' => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:50',
            'staff_type' => 'required|in:Teaching,Non-Teaching',
            'position' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'subjects_taught' => 'nullable|string',
            'date_joined' => 'required|date',
            'employee_number' => 'nullable|string|max:100',
            'employment_type' => 'required|in:Full-Time,Part-Time,Contract',
            'highest_qualification' => 'nullable|string|max:255',
            'institution_attended' => 'nullable|string|max:255',
            'year_of_graduation' => 'nullable|integer|min:1900|max:' . date('Y'),
            'certifications' => 'nullable|string',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'payment_frequency' => 'required|in:Monthly,Weekly,Daily',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'mobile_money_number' => 'nullable|string|max:50',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $validated['school_id'] = $school->id;
        $validated['status'] = 'active';
        $validated['allowances'] = $validated['allowances'] ?? 0;

        // Handle file uploads
        if ($request->hasFile('cv')) {
            $file = $request->file('cv');
            $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $uploadPath = public_path('uploads/staff-documents/' . $school->id);
            if (!file_exists($uploadPath)) { mkdir($uploadPath, 0755, true); }
            $file->move($uploadPath, $filename);
            $validated['cv_path'] = 'uploads/staff-documents/' . $school->id . '/' . $filename;
        }

        if ($request->hasFile('certificate')) {
            $file = $request->file('certificate');
            $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $uploadPath = public_path('uploads/staff-documents/' . $school->id);
            if (!file_exists($uploadPath)) { mkdir($uploadPath, 0755, true); }
            $file->move($uploadPath, $filename);
            $validated['certificate_path'] = 'uploads/staff-documents/' . $school->id . '/' . $filename;
        }

        if ($request->hasFile('id_photo')) {
            $file = $request->file('id_photo');
            $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $uploadPath = public_path('uploads/staff-photos/' . $school->id);
            if (!file_exists($uploadPath)) { mkdir($uploadPath, 0755, true); }
            $file->move($uploadPath, $filename);
            $validated['id_photo_path'] = 'uploads/staff-photos/' . $school->id . '/' . $filename;
        }

        $staff = Staff::create($validated);

        return redirect()->route('school.staff.index')
            ->with('success', 'Staff member added successfully! Staff ID: ' . $staff->staff_id);
    }

    /**
     * Display the specified resource.
     */
    public function show(Staff $staff)
    {
        $this->authorize('view', $staff);
        
        $staff->load(['classes', 'school']);

        return view('school.staff.show', compact('staff'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Staff $staff)
    {
        $this->authorize('update', $staff);

        return view('school.staff.edit', compact('staff'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Staff $staff)
    {
        $this->authorize('update', $staff);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'other_names' => 'nullable|string|max:255',
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date',
            'nationality' => 'nullable|string|max:100',
            'national_id' => 'nullable|string|max:50',
            'religion' => 'nullable|string|max:100',
            'phone_number' => 'required|string|max:50',
            'email' => 'nullable|email|max:191',
            'address' => 'nullable|string',
            'district' => 'nullable|string|max:100',
            'next_of_kin_name' => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:50',
            'staff_type' => 'required|in:Teaching,Non-Teaching',
            'position' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'subjects_taught' => 'nullable|string',
            'date_joined' => 'required|date',
            'employee_number' => 'nullable|string|max:100',
            'employment_type' => 'required|in:Full-Time,Part-Time,Contract',
            'highest_qualification' => 'nullable|string|max:255',
            'institution_attended' => 'nullable|string|max:255',
            'year_of_graduation' => 'nullable|integer|min:1900|max:' . date('Y'),
            'certifications' => 'nullable|string',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'payment_frequency' => 'required|in:Monthly,Weekly,Daily',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'mobile_money_number' => 'nullable|string|max:50',
            'status' => 'required|in:active,on_leave,suspended,terminated,resigned',
            'termination_date' => 'nullable|date',
            'termination_reason' => 'nullable|string',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $validated['allowances'] = $validated['allowances'] ?? 0;

        // Handle file uploads
        if ($request->hasFile('cv')) {
            if ($staff->cv_path) {
                Storage::disk('public')->delete($staff->cv_path);
            }
            $validated['cv_path'] = $request->file('cv')->store('staff-documents/' . $staff->school_id, 'public');
        }

        if ($request->hasFile('certificate')) {
            if ($staff->certificate_path) {
                Storage::disk('public')->delete($staff->certificate_path);
            }
            $validated['certificate_path'] = $request->file('certificate')->store('staff-documents/' . $staff->school_id, 'public');
        }

        if ($request->hasFile('id_photo')) {
            if ($staff->id_photo_path) {
                Storage::disk('public')->delete($staff->id_photo_path);
            }
            $validated['id_photo_path'] = $request->file('id_photo')->store('staff-photos/' . $staff->school_id, 'public');
        }

        $staff->update($validated);

        return redirect()->route('school.staff.index')
            ->with('success', 'Staff member updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Staff $staff)
    {
        $this->authorize('delete', $staff);

        // Delete uploaded files
        if ($staff->cv_path) {
            Storage::disk('public')->delete($staff->cv_path);
        }
        if ($staff->certificate_path) {
            Storage::disk('public')->delete($staff->certificate_path);
        }
        if ($staff->id_photo_path) {
            Storage::disk('public')->delete($staff->id_photo_path);
        }

        $staff->delete();

        return redirect()->route('school.staff.index')
            ->with('success', 'Staff member deleted successfully!');
    }
}
