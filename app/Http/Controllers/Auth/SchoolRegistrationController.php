<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SchoolRegistrationController extends Controller
{
    /**
     * Show the school registration form.
     */
    public function show()
    {
        return view('auth.school-register');
    }

    /**
     * Handle school registration.
     */
    public function store(Request $request)
    {
        // Validate the registration data
        $validator = Validator::make($request->all(), [
            'school_name' => 'required|string|max:255',
            'school_type' => 'required|in:Primary,Secondary,Primary & Secondary,Nursery,University,College,Other',
            'ownership' => 'required|in:Government,Private,Religious,Community,NGO',
            'registration_number' => 'nullable|string|max:100',
            'contact_person' => 'required|string|max:255',
            'contact_position' => 'nullable|string|max:100',
            'email' => 'required|email|unique:schools,email|unique:users,email',
            'phone' => 'required|string|max:50',
            'physical_address' => 'required|string',
            'district' => 'required|string|max:100',
            'district_other' => 'nullable|string|max:100',
            'sub_county' => 'nullable|string|max:100',
            'admin_password' => 'required|string|min:8|confirmed',
            'complete_assessment_now' => 'required|accepted', // Assessment is now MANDATORY
        ], [
            'complete_assessment_now.required' => 'You must complete the full assessment to proceed with registration.',
            'complete_assessment_now.accepted' => 'You must complete the full assessment to proceed with registration.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Create the school record (WITHOUT creating user yet - only after assessment)
            $school = School::create([
                'school_name' => $request->school_name,
                'school_type' => $request->school_type,
                'ownership' => $request->ownership,
                'registration_number' => $request->registration_number,
                'contact_person' => $request->contact_person,
                'contact_position' => $request->contact_position,
                'email' => $request->email,
                'phone' => $request->phone,
                'physical_address' => $request->physical_address,
                'district' => $request->district,
                'district_other' => $request->district_other,
                'sub_county' => $request->sub_county,
                'admin_password' => Hash::make($request->admin_password),
                'password_set_at' => now(),
                'status' => 'pending',
                'branch_id' => null,
                'assessment_complete' => false, // Mark as incomplete initially
            ]);

            // Store school info in session for assessment
            session([
                'pending_school_id' => $school->id,
                'pending_school_email' => $school->email,
                'pending_school_password' => $request->admin_password, // Store for user creation after assessment
            ]);
            
            \Log::info('School registered, redirecting to assessment', [
                'school_id' => $school->id,
                'email' => $school->email
            ]);
            
            return redirect()->route('school.assessment')
                ->with('info', 'Basic registration saved! Please complete the comprehensive assessment below. This is required for approval.');

        } catch (\Exception $e) {
            \Log::error('School registration failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Registration failed: ' . $e->getMessage())
                        ->withInput();
        }
    }

    /**
     * Show the comprehensive school assessment form
     */
    public function showAssessment()
    {
        // Check if there's a pending school registration
        $schoolId = session('pending_school_id');
        $schoolEmail = session('pending_school_email');

        \Log::info('Assessment page accessed', [
            'session_school_id' => $schoolId,
            'session_email' => $schoolEmail,
            'all_sessions' => session()->all()
        ]);

        if (!$schoolId) {
            \Log::warning('No school ID in session, redirecting to register');
            return redirect()->route('school.register')
                ->with('error', 'Please complete basic registration first.');
        }

        $school = School::find($schoolId);
        
        if (!$school) {
            \Log::error('School not found', ['school_id' => $schoolId]);
            return redirect()->route('school.register')
                ->with('error', 'School not found. Please register again.');
        }

        // Ensure we have fresh data
        $school = $school->fresh();

        \Log::info('Assessment page loaded', ['school' => $school->school_name]);

        return view('auth.school-assessment', compact('school'));
    }

    /**
     * Store the comprehensive assessment data
     */
    public function storeAssessment(Request $request)
    {
        $schoolId = session('pending_school_id');
        
        if (!$schoolId) {
            return redirect()->route('school.register')
                ->with('error', 'Session expired. Please register again.');
        }

        $school = School::find($schoolId);
        
        if (!$school) {
            return redirect()->route('school.register')
                ->with('error', 'School not found.');
        }

        // Validate comprehensive assessment data
        $validated = $request->validate([
            // Section 1: Extended School Identification
            'date_of_establishment' => 'nullable|date',
            'school_types' => 'nullable|array',
            'school_types.*' => 'nullable|string|in:Nursery,Primary,Secondary,Vocational,Other',
            'school_type_other' => 'nullable|string|max:255',
            'ownership_type_other' => 'nullable|string|max:255',
            
            // Section 2: Extended Location
            'county' => 'nullable|string|max:100',
            'county_other' => 'nullable|string|max:100',
            'parish' => 'nullable|string|max:100',
            'parish_other' => 'nullable|string|max:100',
            'village' => 'nullable|string|max:100',
            'village_other' => 'nullable|string|max:100',
            'gps_coordinates' => 'nullable|string|max:255',
            
            // Section 3: Extended Contact
            'school_phone_number' => 'nullable|string|max:50',
            'school_email_address' => 'nullable|email|max:191',
            'website' => 'nullable|url|max:255',
            'administrator_name' => 'nullable|string|max:255',
            'administrator_contact_number' => 'nullable|string|max:50',
            'administrator_email' => 'nullable|email|max:191',
            
            // Section 4: Staffing & Enrollment
            'total_teaching_staff' => 'nullable|integer|min:0',
            'total_non_teaching_staff' => 'nullable|integer|min:0',
            'current_student_enrollment' => 'nullable|integer|min:0',
            'maximum_student_capacity' => 'nullable|integer|min:0',
            'average_tuition_fees_per_term' => 'nullable|numeric|min:0',
            'student_fees_file' => 'nullable|file|mimes:xlsx,xls|max:5120',
            'other_income_sources' => 'nullable|string',
            'income_sources' => 'nullable|array',
            'income_sources.*' => 'nullable|string',
            'income_amounts' => 'nullable|array',
            'income_amounts.*' => 'nullable|numeric|min:0',
            
            // Section 5: Infrastructure
            'number_of_classrooms' => 'nullable|integer|min:0',
            'number_of_dormitories' => 'nullable|integer|min:0',
            'number_of_toilets' => 'nullable|integer|min:0',
            'has_electricity' => 'nullable|boolean',
            'electricity_provider' => 'nullable|string|max:255',
            'electricity_provider_other' => 'nullable|string|max:255',
            'water_source' => 'nullable|string|max:255',
            'has_internet_access' => 'nullable|boolean',
            'internet_provider' => 'nullable|string|max:255',
            'internet_provider_other' => 'nullable|string|max:255',
            'transport_assets' => 'nullable|array',
            'transport_assets.*' => 'nullable|string',
            'transport_assets_other' => 'nullable|string|max:255',
            'learning_resources' => 'nullable|array',
            'learning_resources.*' => 'nullable|string',
            'learning_resources_other' => 'nullable|string|max:255',
            
            // Section 6: Financial Projections
            'first_month_revenue' => 'nullable|numeric|min:0',
            'last_month_expenditure' => 'nullable|numeric|min:0',
            'expense_categories' => 'nullable|array',
            'expense_categories.*' => 'nullable|string',
            'expense_amounts' => 'nullable|array',
            'expense_amounts.*' => 'nullable|numeric|min:0',
            'past_two_terms_shortfall' => 'nullable|numeric|min:0',
            'expected_shortfall_this_term' => 'nullable|numeric|min:0',
            'unpaid_students_list' => 'nullable|string',
            'unpaid_students_file' => 'nullable|file|mimes:xlsx,xls,csv|max:5120',
            'reserve_funds_status' => 'nullable|string',
            
            // Section 7: Financial Performance
            'average_monthly_income' => 'nullable|numeric|min:0',
            'average_monthly_expenses' => 'nullable|numeric|min:0',
            'profit_or_surplus' => 'nullable|numeric',
            'banking_institutions_used' => 'nullable|string|max:255',
            'banking_institutions_other' => 'nullable|string|max:255',
            'has_audited_statements' => 'nullable|boolean',
            
            // Section 8: Loan Request
            'loan_amount_requested' => 'nullable|numeric|min:0',
            'loan_purpose' => 'nullable|string',
            'preferred_repayment_period' => 'nullable|string|max:100',
            'proposed_monthly_installment' => 'nullable|numeric|min:0',
            'has_received_loan_before' => 'nullable|boolean',
            'previous_loan_details' => 'nullable|string',
            
            // Section 9: Supporting Documents
            'registration_certificate' => 'nullable|sometimes|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'school_license' => 'nullable|sometimes|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'audited_statements' => 'nullable|sometimes|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'bank_statements' => 'nullable|sometimes|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'owner_national_id' => 'nullable|sometimes|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'land_title' => 'nullable|sometimes|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'existing_loan_agreements' => 'nullable|sometimes|file|mimes:pdf,jpg,jpeg,png|max:5120',
            
            // Section 10: Institutional Standing
            'current_assets_list' => 'nullable|string',
            'current_liabilities_list' => 'nullable|string',
            'debtors_creditors_list' => 'nullable|string',
            'ministry_of_education_standing' => 'nullable|string|max:255',
            'license_validity_status' => 'nullable|string|max:255',
            'owner_names' => 'nullable|array',
            'owner_names.*' => 'nullable|string|max:255',
            'owner_percentages' => 'nullable|array',
            'owner_percentages.*' => 'nullable|numeric|min:0|max:100',
            'has_outstanding_loans' => 'nullable|boolean',
            'outstanding_loans_details' => 'nullable|string',
            'has_assets_as_collateral' => 'nullable|boolean',
            'collateral_assets_details' => 'nullable|string',
            
            // Section 11: Declarations
            'declaration_name' => 'nullable|string|max:255',
            'declaration_date' => 'nullable|date',
            'consent_to_share_information' => 'nullable|boolean',
        ]);

        try {
            // Handle file uploads with correct field mapping
            $documentPaths = [];
            $documentFieldMapping = [
                'registration_certificate' => 'registration_certificate_path',
                'school_license' => 'school_license_path',
                'audited_statements' => 'audited_statements_path',
                'bank_statements' => 'bank_statements_path',
                'owner_national_id' => 'owner_national_id_path',
                'land_title' => 'land_title_path',
                'existing_loan_agreements' => 'existing_loan_agreements_path'
            ];

            foreach ($documentFieldMapping as $formField => $dbColumn) {
                if ($request->hasFile($formField)) {
                    $file = $request->file($formField);
                    $filename = time() . '_' . $formField . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('school-documents/' . $schoolId, $filename, 'public');
                    $documentPaths[$dbColumn] = $path;
                    
                    \Log::info('Document uploaded', [
                        'school_id' => $schoolId,
                        'field' => $formField,
                        'db_column' => $dbColumn,
                        'path' => $path
                    ]);
                }
            }

            // Handle student fees file upload
            if ($request->hasFile('student_fees_file')) {
                $file = $request->file('student_fees_file');
                $filename = time() . '_student_fees.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('school-documents/' . $schoolId, $filename, 'public');
                $documentPaths['student_fees_file_path'] = $path;
                
                \Log::info('Student fees file uploaded', [
                    'school_id' => $schoolId,
                    'path' => $path
                ]);
            }

            // Handle unpaid students file upload
            if ($request->hasFile('unpaid_students_file')) {
                $file = $request->file('unpaid_students_file');
                $filename = time() . '_unpaid_students.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('school-documents/' . $schoolId, $filename, 'public');
                $documentPaths['unpaid_students_file_path'] = $path;
                
                \Log::info('Unpaid students file uploaded', [
                    'school_id' => $schoolId,
                    'path' => $path
                ]);
            }

            // Process income sources and amounts
            $incomeSources = [];
            $incomeAmounts = [];
            if ($request->has('income_sources') && is_array($request->income_sources)) {
                $sources = $request->income_sources;
                $amounts = $request->income_amounts ?? [];
                
                foreach ($sources as $index => $source) {
                    if (!empty($source)) {
                        $incomeSources[] = $source;
                        $incomeAmounts[] = isset($amounts[$index]) ? floatval($amounts[$index]) : 0;
                    }
                }
            }

            // Process expense breakdown
            $expenseBreakdown = [];
            if ($request->has('expense_categories') && $request->has('expense_amounts')) {
                $categories = $request->expense_categories;
                $amounts = $request->expense_amounts;
                
                foreach ($categories as $index => $category) {
                    if (!empty($category) && isset($amounts[$index])) {
                        $expenseBreakdown[$category] = $amounts[$index];
                    }
                }
            }

            // Process ownership details
            $ownershipList = [];
            if ($request->has('owner_names') && $request->has('owner_percentages')) {
                $names = $request->owner_names;
                $percentages = $request->owner_percentages;
                
                $totalPercentage = 0;
                foreach ($names as $index => $name) {
                    if (!empty($name) && isset($percentages[$index])) {
                        $percentage = floatval($percentages[$index]);
                        $ownershipList[] = [
                            'name' => $name,
                            'percentage' => $percentage
                        ];
                        $totalPercentage += $percentage;
                    }
                }
                
                // Validate that total ownership equals 100%
                if (count($ownershipList) > 0 && abs($totalPercentage - 100) > 0.01) {
                    return back()->withErrors(['ownership' => 'Total ownership percentage must equal 100%. Current total: ' . number_format($totalPercentage, 2) . '%'])->withInput();
                }
            }

            // Process school_types (checkboxes)
            $schoolTypes = [];
            if ($request->has('school_types') && is_array($request->school_types)) {
                $schoolTypes = array_values(array_filter($request->school_types));
            }

            // Process transport_assets (checkboxes)
            $transportAssets = [];
            if ($request->has('transport_assets') && is_array($request->transport_assets)) {
                $transportAssets = array_values(array_filter($request->transport_assets));
            }

            // Process learning_resources (checkboxes)
            $learningResources = [];
            if ($request->has('learning_resources') && is_array($request->learning_resources)) {
                $learningResources = array_values(array_filter($request->learning_resources));
            }

            // Process expense categories and amounts separately (new JSON fields)
            $expenseCategories = [];
            $expenseAmounts = [];
            if ($request->has('expense_categories') && is_array($request->expense_categories)) {
                $categories = $request->expense_categories;
                $amounts = $request->expense_amounts ?? [];
                
                foreach ($categories as $index => $category) {
                    if (!empty($category)) {
                        $expenseCategories[] = $category;
                        $expenseAmounts[] = isset($amounts[$index]) ? floatval($amounts[$index]) : 0;
                    }
                }
            }

            // Update school with assessment data
            $updateData = array_merge($validated, $documentPaths);
            $updateData['expense_breakdown'] = !empty($expenseBreakdown) ? json_encode($expenseBreakdown) : null;
            $updateData['income_sources'] = !empty($incomeSources) ? json_encode($incomeSources) : null;
            $updateData['income_amounts'] = !empty($incomeAmounts) ? json_encode($incomeAmounts) : null;
            $updateData['ownership_details'] = !empty($ownershipList) ? json_encode($ownershipList) : null;
            
            // Add new JSON fields
            $updateData['school_types'] = !empty($schoolTypes) ? json_encode($schoolTypes) : null;
            $updateData['transport_assets'] = !empty($transportAssets) ? json_encode($transportAssets) : null;
            $updateData['learning_resources_available'] = !empty($learningResources) ? json_encode($learningResources) : null;
            $updateData['expense_categories'] = !empty($expenseCategories) ? json_encode($expenseCategories) : null;
            $updateData['expense_amounts'] = !empty($expenseAmounts) ? json_encode($expenseAmounts) : null;
            
            // Calculate assessment completion percentage
            $assessableFields = [
                'date_of_establishment', 'school_types', 'school_type_other', 'ownership_type_other',
                'county', 'county_other', 'parish', 'parish_other', 'village', 'village_other', 'gps_coordinates',
                'school_phone_number', 'school_email_address', 'website', 'administrator_name', 'administrator_contact_number', 'administrator_email',
                'total_teaching_staff', 'total_non_teaching_staff', 'current_student_enrollment', 'maximum_student_capacity', 
                'average_tuition_fees_per_term', 'student_fees_file_path', 'income_sources', 'income_amounts',
                'number_of_classrooms', 'number_of_dormitories', 'number_of_toilets', 'has_electricity', 'electricity_provider', 
                'electricity_provider_other', 'water_source', 'has_internet_access', 'internet_provider', 'internet_provider_other',
                'transport_assets', 'transport_assets_other', 'learning_resources_available', 'learning_resources_other',
                'first_month_revenue', 'last_month_expenditure', 'expense_breakdown', 'expense_categories', 'expense_amounts',
                'past_two_terms_shortfall', 'expected_shortfall_this_term', 'unpaid_students_list', 'unpaid_students_file_path', 'reserve_funds_status',
                'average_monthly_income', 'average_monthly_expenses', 'profit_or_surplus', 'banking_institutions_used', 
                'banking_institutions_other', 'has_audited_statements', 'audited_statements_path',
                'registration_certificate_path', 'school_license_path', 'bank_statements_path', 'owner_national_id_path', 
                'land_title_path', 'existing_loan_agreements_path',
                'current_assets_list', 'current_liabilities_list', 'debtors_creditors_list', 'ministry_of_education_standing', 
                'license_validity_status', 'license_copy_path', 'ownership_details', 'has_outstanding_loans', 
                'outstanding_loans_details', 'has_assets_as_collateral', 'collateral_assets_details',
                'declaration_name', 'declaration_signature_path', 'declaration_date', 'consent_to_share_information'
            ];
            
            $filledCount = 0;
            $totalFields = count($assessableFields);
            
            foreach ($assessableFields as $field) {
                $value = $updateData[$field] ?? null;
                // Count field as filled if it has a value (including false/0 for booleans)
                if (!empty($value) || $value === false || $value === 0 || $value === '0') {
                    $filledCount++;
                }
            }
            
            $completionPercentage = round(($filledCount / $totalFields) * 100, 2);
            
            // Validation: Minimum 65% required to save assessment
            if ($completionPercentage < 65) {
                \Log::warning('Assessment submission rejected - below 65%', [
                    'school_id' => $schoolId,
                    'completion' => $completionPercentage,
                    'filled_fields' => $filledCount,
                    'total_fields' => $totalFields
                ]);
                
                return back()->withErrors([
                    'assessment' => "Assessment is only {$completionPercentage}% complete. Please fill at least 65% of the assessment form before submitting. You have filled {$filledCount} out of {$totalFields} required fields. Please complete more sections before submitting."
                ])->withInput();
            }
            
            // Assessment is complete (65% or more)
            $updateData['assessment_complete'] = true;
            $updateData['assessment_completed_at'] = now();
            
            \Log::info('Assessment completion calculated', [
                'school_id' => $schoolId,
                'completion_percentage' => $completionPercentage,
                'filled_fields' => $filledCount,
                'total_fields' => $totalFields,
                'marked_complete' => $updateData['assessment_complete']
            ]);

            $school->update($updateData);

            // NOW create the user account (only after assessment is complete)
            $password = session('pending_school_password');
            
            // Check if user already exists
            $existingUser = User::where('email', $school->email)->first();
            
            if (!$existingUser) {
                User::create([
                    'name' => $school->contact_person,
                    'email' => $school->email,
                    'password' => Hash::make($password),
                    'user_type' => 'school',
                    'school_id' => $school->id,
                    'branch_id' => null,
                    'status' => 'pending',
                    'phone' => $school->phone,
                    'address' => $school->physical_address,
                    'designation' => $school->contact_position ?? 'School Administrator',
                ]);
                
                \Log::info('User account created after assessment', [
                    'school_id' => $school->id,
                    'email' => $school->email
                ]);
            }

            // Clear session
            session()->forget(['pending_school_id', 'pending_school_email', 'pending_school_password']);

            // Success message for complete assessment (75%+)
            $message = "🎉 Registration Successful! Your school assessment is {$completionPercentage}% complete and has been submitted for approval. " .
                "You will be notified via email once approved. If delayed, please contact the administrator on +256708356505. " .
                "After approval, you can login with your registered email and password.";

            return redirect()->route('login')->with('success', $message);
            
        } catch (\Exception $e) {
            \Log::error('Assessment submission failed', [
                'school_id' => $schoolId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Failed to save assessment data: ' . $e->getMessage())
                        ->withInput();
        }
    }

    /**
     * Show the form to enter email for completing assessment
     */
    public function showCompleteAssessment()
    {
        return view('auth.school-complete-assessment');
    }

    /**
     * Verify email and continue to assessment
     */
    public function continueAssessment(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        // Find school by email
        $school = School::where('email', $validated['email'])->first();

        if (!$school) {
            return back()->with('error', 'No school found with this email address. Please check and try again.')
                        ->withInput();
        }

        // Check if assessment is already complete
        if ($school->assessment_complete) {
            $message = 'Your assessment has already been completed and submitted successfully. ';
            
            // Add status-specific message
            if ($school->status === 'approved') {
                $message .= 'Your school has been approved! You can now login with your credentials.';
            } elseif ($school->status === 'rejected') {
                $message .= 'Please contact the admin on +256708356505 for more information.';
            } else {
                $message .= 'Please wait for your school to be approved by the admin. For inquiries, contact us on +256708356505.';
            }
            
            return back()->with('warning', $message)->withInput();
        }

        // Set up session for assessment continuation
        session([
            'pending_school_id' => $school->id,
            'pending_school_email' => $school->email,
            'pending_school_password' => null, // Password already exists in database
        ]);

        \Log::info('Assessment continuation initiated', [
            'school_id' => $school->id,
            'email' => $school->email,
            'school_name' => $school->school_name
        ]);

        return redirect()->route('school.assessment')
            ->with('info', 'Welcome back! Please complete the assessment for ' . $school->school_name);
    }
}
