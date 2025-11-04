<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ClassesController extends Controller
{
    use AuthorizesRequests;
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', SchoolClass::class);
        
        $school = auth()->user()->school;
        
        $classes = SchoolClass::where('school_id', $school->id)
            ->with(['classTeacher', 'students'])
            ->withCount('students')
            ->orderBy('class_name')
            ->paginate(15);

        return view('school.classes.index', compact('school', 'classes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', SchoolClass::class);
        
        $school = auth()->user()->school;
        
        // Get available teachers (teaching staff who are not already class teachers)
        $teachers = Staff::where('school_id', $school->id)
            ->where('staff_type', 'Teaching')
            ->where('status', 'active')
            ->get();

        return view('school.classes.create', compact('school', 'teachers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', SchoolClass::class);
        
        $school = auth()->user()->school;

        $validated = $request->validate([
            'class_name' => 'required|string|max:255',
            'class_code' => 'nullable|string|max:50',
            'level' => 'nullable|string|max:100',
            'stream' => 'nullable|string|max:100',
            'class_teacher_id' => 'nullable|exists:staff,id',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'academic_year' => 'nullable|string|max:50',
        ]);

        $validated['school_id'] = $school->id;
        $validated['current_enrollment'] = 0;
        $validated['status'] = 'active';

        SchoolClass::create($validated);

        return redirect()->route('school.classes.index')
            ->with('success', 'Class created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(SchoolClass $class)
    {
        $this->authorize('view', $class);

        $class->load(['classTeacher', 'students', 'school']);

        return view('school.classes.show', compact('class'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SchoolClass $class)
    {
        $this->authorize('update', $class);

        $school = auth()->user()->school;
        
        $teachers = Staff::where('school_id', $school->id)
            ->where('staff_type', 'Teaching')
            ->where('status', 'active')
            ->get();

        return view('school.classes.edit', compact('class', 'teachers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SchoolClass $class)
    {
        $this->authorize('update', $class);

        $validated = $request->validate([
            'class_name' => 'required|string|max:255',
            'class_code' => 'nullable|string|max:50',
            'level' => 'nullable|string|max:100',
            'stream' => 'nullable|string|max:100',
            'class_teacher_id' => 'nullable|exists:staff,id',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'academic_year' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive,archived',
        ]);

        $class->update($validated);

        return redirect()->route('school.classes.index')
            ->with('success', 'Class updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SchoolClass $class)
    {
        $this->authorize('delete', $class);

        // Check if class has students
        if ($class->students()->count() > 0) {
            return back()->with('error', 'Cannot delete class with enrolled students. Please transfer students first.');
        }

        $class->delete();

        return redirect()->route('school.classes.index')
            ->with('success', 'Class deleted successfully!');
    }
}
