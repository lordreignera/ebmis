<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StudentsController extends Controller
{
    use AuthorizesRequests;
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Student::class);
        
        $school = auth()->user()->school;
        
        $query = Student::where('school_id', $school->id)
            ->with('class');

        // Filter by class
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
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
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('admission_number', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('first_name')->paginate(20);
        
        $classes = SchoolClass::where('school_id', $school->id)
            ->where('status', 'active')
            ->get();

        return view('school.students.index', compact('school', 'students', 'classes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Student::class);
        
        $school = auth()->user()->school;
        
        $classes = SchoolClass::where('school_id', $school->id)
            ->where('status', 'active')
            ->get();

        return view('school.students.create', compact('school', 'classes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Student::class);
        
        $school = auth()->user()->school;

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'other_names' => 'nullable|string|max:255',
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date',
            'place_of_birth' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:100',
            'religion' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'district' => 'nullable|string|max:100',
            'village' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'parent_name' => 'required|string|max:255',
            'parent_phone' => 'required|string|max:50',
            'parent_email' => 'nullable|email|max:191',
            'parent_occupation' => 'nullable|string|max:255',
            'parent_address' => 'nullable|string',
            'relationship' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'class_id' => 'nullable|exists:school_classes,id',
            'admission_number' => 'nullable|string|max:100',
            'admission_date' => 'nullable|date',
            'previous_school' => 'nullable|string|max:255',
            'academic_year' => 'nullable|string|max:50',
            'boarding_status' => 'required|in:Day,Boarding',
            'blood_group' => 'nullable|string|max:10',
            'allergies' => 'nullable|string',
            'medical_conditions' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $validated['school_id'] = $school->id;
        $validated['status'] = 'active';

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('student-photos/' . $school->id, 'public');
            $validated['photo_path'] = $path;
        }

        $student = Student::create($validated);

        // Update class enrollment count
        if ($student->class_id) {
            $class = SchoolClass::find($student->class_id);
            $class->increment('current_enrollment');
        }

        return redirect()->route('school.students.index')
            ->with('success', 'Student added successfully! Student ID: ' . $student->student_id);
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        $this->authorize('view', $student);
        
        $student->load(['class', 'school']);

        return view('school.students.show', compact('student'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student)
    {
        $this->authorize('update', $student);

        $school = auth()->user()->school;
        
        $classes = SchoolClass::where('school_id', $school->id)
            ->where('status', 'active')
            ->get();

        return view('school.students.edit', compact('student', 'classes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        $this->authorize('update', $student);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'other_names' => 'nullable|string|max:255',
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date',
            'place_of_birth' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:100',
            'religion' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'district' => 'nullable|string|max:100',
            'village' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'parent_name' => 'required|string|max:255',
            'parent_phone' => 'required|string|max:50',
            'parent_email' => 'nullable|email|max:191',
            'parent_occupation' => 'nullable|string|max:255',
            'parent_address' => 'nullable|string',
            'relationship' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'class_id' => 'nullable|exists:school_classes,id',
            'admission_number' => 'nullable|string|max:100',
            'admission_date' => 'nullable|date',
            'previous_school' => 'nullable|string|max:255',
            'academic_year' => 'nullable|string|max:50',
            'boarding_status' => 'required|in:Day,Boarding',
            'blood_group' => 'nullable|string|max:10',
            'allergies' => 'nullable|string',
            'medical_conditions' => 'nullable|string',
            'status' => 'required|in:active,suspended,transferred,graduated,expelled',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($student->photo_path) {
                Storage::disk('public')->delete($student->photo_path);
            }
            $path = $request->file('photo')->store('student-photos/' . $student->school_id, 'public');
            $validated['photo_path'] = $path;
        }

        // Update class enrollment if class changed
        $oldClassId = $student->class_id;
        $newClassId = $validated['class_id'] ?? null;

        if ($oldClassId != $newClassId) {
            if ($oldClassId) {
                SchoolClass::find($oldClassId)->decrement('current_enrollment');
            }
            if ($newClassId) {
                SchoolClass::find($newClassId)->increment('current_enrollment');
            }
        }

        $student->update($validated);

        return redirect()->route('school.students.index')
            ->with('success', 'Student updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        $this->authorize('delete', $student);

        // Update class enrollment
        if ($student->class_id) {
            SchoolClass::find($student->class_id)->decrement('current_enrollment');
        }

        // Delete photo
        if ($student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
        }

        $student->delete();

        return redirect()->route('school.students.index')
            ->with('success', 'Student deleted successfully!');
    }

    /**
     * Import students from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $school = auth()->user()->school;

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row
            array_shift($rows);

            $imported = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) continue;

                    $studentData = [
                        'school_id' => $school->id,
                        'first_name' => $row[0] ?? null,
                        'last_name' => $row[1] ?? null,
                        'other_names' => $row[2] ?? null,
                        'gender' => $row[3] ?? null,
                        'date_of_birth' => $row[4] ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[4])->format('Y-m-d') : null,
                        'parent_name' => $row[5] ?? null,
                        'parent_phone' => $row[6] ?? null,
                        'boarding_status' => $row[7] ?? 'Day',
                        'status' => 'active',
                    ];

                    // Validate required fields
                    if (empty($studentData['first_name']) || empty($studentData['last_name']) || 
                        empty($studentData['gender']) || empty($studentData['parent_name']) || 
                        empty($studentData['parent_phone'])) {
                        $errors[] = "Row " . ($index + 2) . ": Missing required fields";
                        continue;
                    }

                    Student::create($studentData);
                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            $message = "Successfully imported {$imported} students.";
            if (count($errors) > 0) {
                $message .= " Errors: " . implode(', ', array_slice($errors, 0, 5));
            }

            return redirect()->route('school.students.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download Excel template
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'First Name*', 
            'Last Name*', 
            'Other Names', 
            'Gender* (Male/Female)', 
            'Date of Birth (YYYY-MM-DD)', 
            'Parent Name*', 
            'Parent Phone*', 
            'Boarding Status (Day/Boarding)'
        ];

        $sheet->fromArray($headers, null, 'A1');

        // Style header row
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:H1')->getFont()->getColor()->setRGB('FFFFFF');

        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'student_import_template.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);

        $writer->save($temp_file);

        return response()->download($temp_file, $fileName)->deleteFileAfterSend(true);
    }
}
