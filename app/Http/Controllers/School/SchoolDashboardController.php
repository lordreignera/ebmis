<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Staff;
use Illuminate\Http\Request;

class SchoolDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Check if user is a school user
        if ($user->user_type !== 'school') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $school = $user->school;

        // Check if school exists and is approved
        if (!$school) {
            return view('school.pending')->with('error', 'School not found.');
        }

        if ($school->status !== 'approved') {
            return view('school.pending', compact('school'));
        }

        // Get dashboard statistics
        $stats = [
            'total_students' => Student::where('school_id', $school->id)->where('status', 'active')->count(),
            'total_staff' => Staff::where('school_id', $school->id)->where('status', 'active')->count(),
            'total_classes' => SchoolClass::where('school_id', $school->id)->where('status', 'active')->count(),
            'teaching_staff' => Staff::where('school_id', $school->id)->where('status', 'active')->where('staff_type', 'Teaching')->count(),
            'non_teaching_staff' => Staff::where('school_id', $school->id)->where('status', 'active')->where('staff_type', 'Non-Teaching')->count(),
        ];

        // Recent students
        $recentStudents = Student::where('school_id', $school->id)
            ->with('class')
            ->latest()
            ->take(5)
            ->get();

        // Recent staff
        $recentStaff = Staff::where('school_id', $school->id)
            ->latest()
            ->take(5)
            ->get();

        // Classes with enrollment
        $classes = SchoolClass::where('school_id', $school->id)
            ->where('status', 'active')
            ->withCount('students')
            ->get();

        return view('school.dashboard', compact('school', 'stats', 'recentStudents', 'recentStaff', 'classes'));
    }
}
