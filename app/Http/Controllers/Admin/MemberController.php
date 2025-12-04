<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberType;
use App\Models\Country;
use App\Models\Branch;
use App\Models\Group;
use App\Models\PlaceOfBirth;
use App\Rules\UniqueMemberIdentifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    /**
     * Display a listing of members
     */
    public function index(Request $request)
    {
        $query = Member::with(['country', 'branch', 'group', 'addedBy']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('fname', 'like', "%{$search}%")
                  ->orWhere('lname', 'like', "%{$search}%")
                  ->orWhere('mname', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('nin', 'like', "%{$search}%")
                  ->orWhere('contact', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  // Search by full name (first + last name combined)
                  ->orWhereRaw("CONCAT(COALESCE(fname, ''), ' ', COALESCE(lname, '')) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CONCAT(COALESCE(lname, ''), ' ', COALESCE(fname, '')) LIKE ?", ["%{$search}%"])
                  // Search by full name with middle name
                  ->orWhereRaw("CONCAT(COALESCE(fname, ''), ' ', COALESCE(mname, ''), ' ', COALESCE(lname, '')) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CONCAT(COALESCE(fname, ''), ' ', COALESCE(lname, ''), ' ', COALESCE(mname, '')) LIKE ?", ["%{$search}%"]);
            });
        }

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by member type
        // Note: Individual members (member_type=1) should show ALL individual members,
        // including those attached to groups, since they were individuals first
        if ($request->filled('member_type')) {
            $query->where('member_type', $request->member_type);
        }

        // Filter by status using scopes
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'approved':
                    $query->approved();
                    break;
                case 'pending':
                    $query->pending();
                    break;
                case 'rejected':
                    $query->rejected();
                    break;
                case 'suspended':
                    $query->suspended();
                    break;
            }
        }

        // Exclude soft deleted
        $query->notDeleted();

        // Use datecreated for ordering (compatible with old data)
        $perPage = $request->get('per_page', 20);
        $members = $query->orderBy('datecreated', 'desc')->paginate($perPage)->appends($request->except('page'));

        $branches = Branch::active()->get();
        $groups = Group::verified()->get();

        // Get statistics
        $stats = [
            'total' => Member::notDeleted()->count(),
            'approved' => Member::approved()->notDeleted()->count(),
            'pending' => Member::pending()->notDeleted()->count(),
            'rejected' => Member::rejected()->notDeleted()->count(),
        ];

        return view('admin.members.index', compact('members', 'branches', 'groups', 'stats'));
    }

    /**
     * Show the form for creating a new member
     */
    public function create()
    {
        // Generate auto account code
        $accountCode = 'PM' . time();
        
        $countries = Country::active()->get();
        $branches = Branch::active()->get();
        $memberTypes = MemberType::active()->get();
        $groups = Group::verified()->get();

        return view('admin.members.create', compact('countries', 'branches', 'memberTypes', 'groups', 'accountCode'));
    }

    /**
     * Store a newly created member
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:members,code',
            'fname' => 'required|string|max:80',
            'lname' => 'required|string|max:80',
            'mname' => 'nullable|string|max:191',
            'nin' => ['required', 'string', 'max:80', new UniqueMemberIdentifier('nin')],
            'contact' => ['required', 'string', 'max:80', new UniqueMemberIdentifier('contact')],
            'alt_contact' => 'nullable|string|max:191',
            'email' => ['nullable', 'email', 'max:191', new UniqueMemberIdentifier('email')],
            'plot_no' => 'nullable|string|max:191',
            'village' => 'nullable|string|max:191',
            'parish' => 'nullable|string|max:191',
            'subcounty' => 'nullable|string|max:191',
            'county' => 'nullable|string|max:191',
            'country_id' => 'required|exists:countries,id',
            // Place of birth fields
            'birth_plot_no' => 'nullable|string|max:80',
            'birth_village' => 'nullable|string|max:80',
            'birth_parish' => 'nullable|string|max:80',
            'birth_subcounty' => 'nullable|string|max:80',
            'birth_county' => 'nullable|string|max:80',
            'birth_country_id' => 'nullable|exists:countries,id',
            // Other fields
            'gender' => 'nullable|in:Male,Female,Other',
            'dob' => 'nullable|date|before:today',
            'fixed_line' => 'nullable|string|max:191',
            'mobile_pin' => 'nullable|string|max:10',
            'member_type' => 'required|exists:member_types,id',
            'group_id' => 'nullable|exists:groups,id',
            'branch_id' => 'required|exists:branches,id',
            'pp_file' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            'id_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        // Additional duplicate check as a safety measure
        $conflicts = Member::getDuplicateConflicts(
            $validated['nin'], 
            $validated['contact'], 
            $validated['email'] ?? null
        );
        
        if (!empty($conflicts)) {
            $errorMessages = [];
            foreach ($conflicts as $field => $conflict) {
                $member = $conflict['existing_member'];
                $errorMessages[$field] = "This {$conflict['field']} is already registered to member: {$member->fname} {$member->lname} (Code: {$member->code})";
            }
            
            return back()->withErrors($errorMessages)->withInput();
        }

        // Handle file uploads
        if ($request->hasFile('pp_file')) {
            $validated['pp_file'] = $request->file('pp_file')->store('member-photos', 'public');
        }

        if ($request->hasFile('id_file')) {
            $validated['id_file'] = $request->file('id_file')->store('member-ids', 'public');
        }

        $validated['added_by'] = auth()->id();
        $validated['verified'] = false; // Default to unverified
        $validated['status'] = 'pending'; // Default to pending approval
        $validated['soft_delete'] = false; // Default to not deleted

        $member = Member::create($validated);

        // Create place of birth record if any birth place data is provided
        if (!empty(array_filter([
            $request->birth_plot_no,
            $request->birth_village, 
            $request->birth_parish,
            $request->birth_subcounty,
            $request->birth_county,
            $request->birth_country_id
        ]))) {
            $member->placeOfBirth()->create([
                'plot_no' => $request->birth_plot_no,
                'village' => $request->birth_village ?? '',
                'parish' => $request->birth_parish ?? '',
                'subcounty' => $request->birth_subcounty ?? '',
                'county' => $request->birth_county ?? '',
                'country_id' => $request->birth_country_id ?? $request->country_id
            ]);
        }

        return redirect()->route('admin.members.index')
                        ->with('success', 'Member created successfully and is pending approval.');
    }

    /**
     * Display the specified member
     */
    public function show(Member $member)
    {
        $member->load([
            'country', 
            'branch', 
            'group', 
            'addedBy',
            'personalLoans.product',
            'personalLoans.schedules',
            'loans.product',
            'loans.schedules',
            'savings.product',
            'guarantees.loan',
            'fees.feeType',
            'fees' => function($query) {
                $query->orderBy('datecreated', 'desc');
            },
            'businesses.businessType',
            'businesses.address',
            'assets.assetType',
            'liabilities.liabilityType',
            'documents.uploadedBy',
            'attachmentLibrary'
        ]);

        // Get fee types for the add fee form
        $feeTypes = \App\Models\FeeType::active()->get();
        
        // Get recent transactions/payments
        $recentPayments = $member->fees()
            ->with('feeType')
            ->orderBy('datecreated', 'desc')
            ->take(10)
            ->get();

        // Get types for dropdowns in assessment forms
        $businessTypes = \App\Models\BusinessType::all();
        $assetTypes = \App\Models\AssetType::all();
        $liabilityTypes = \App\Models\LiabilityType::all();

        return view('admin.members.show', compact(
            'member', 
            'feeTypes', 
            'recentPayments',
            'businessTypes',
            'assetTypes',
            'liabilityTypes'
        ));
    }

    /**
     * Show the form for editing the specified member
     */
    public function edit(Member $member)
    {
        $countries = Country::active()->get();
        $branches = Branch::active()->get();
        $memberTypes = MemberType::active()->get();
        $groups = Group::verified()->get();

        return view('admin.members.edit', compact('member', 'countries', 'branches', 'memberTypes', 'groups'));
    }

    /**
     * Update the specified member
     */
    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'fname' => 'required|string|max:80',
            'lname' => 'required|string|max:80',
            'mname' => 'nullable|string|max:191',
            'nin' => ['required', 'string', 'max:80', Rule::unique('members')->ignore($member->id)],
            'contact' => 'required|string|max:80',
            'alt_contact' => 'nullable|string|max:191',
            'email' => ['nullable', 'email', 'max:191', Rule::unique('members')->ignore($member->id)],
            'plot_no' => 'nullable|string|max:191',
            'village' => 'nullable|string|max:191',
            'parish' => 'nullable|string|max:191',
            'subcounty' => 'nullable|string|max:191',
            'county' => 'nullable|string|max:191',
            'country_id' => 'required|exists:countries,id',
            'gender' => 'required|in:Male,Female,Other',
            'dob' => 'required|date|before:today',
            'fixed_line' => 'nullable|string|max:191',
            'mobile_pin' => 'nullable|string|max:10',
            'member_type' => 'required|exists:member_types,id',
            'group_id' => 'nullable|exists:groups,id',
            'branch_id' => 'required|exists:branches,id',
            'pp_file' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            'id_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'comments' => 'nullable|string|max:150',
            'verified' => 'boolean',
        ]);

        // Handle file uploads - Only update if new file is uploaded
        if ($request->hasFile('pp_file')) {
            // Keep old file as backup (don't delete)
            // You can uncomment below to delete old file if needed
            // if ($member->pp_file) {
            //     Storage::disk('public')->delete($member->pp_file);
            // }
            $validated['pp_file'] = $request->file('pp_file')->store('member-photos', 'public');
        } else {
            // Preserve existing file path
            $validated['pp_file'] = $member->pp_file;
        }

        if ($request->hasFile('id_file')) {
            // Keep old file as backup (don't delete)
            // You can uncomment below to delete old file if needed
            // if ($member->id_file) {
            //     Storage::disk('public')->delete($member->id_file);
            // }
            $validated['id_file'] = $request->file('id_file')->store('member-ids', 'public');
        } else {
            // Preserve existing file path
            $validated['id_file'] = $member->id_file;
        }

        $member->update($validated);

        return redirect()->route('admin.members.show', $member)
                        ->with('success', 'Member updated successfully.');
    }

    /**
     * Verify a member
     */
    public function verify(Member $member)
    {
        $member->update(['verified' => true]);

        return redirect()->back()->with('success', 'Member verified successfully.');
    }

    /**
     * Soft delete a member
     */
    public function destroy(Member $member)
    {
        $member->update([
            'soft_delete' => true,
            'del_user' => auth()->id(),
            'del_comments' => 'Deleted by admin'
        ]);

        return redirect()->route('admin.members.index')
                        ->with('success', 'Member deleted successfully.');
    }

    /**
     * Get member details for AJAX requests
     */
    public function getMemberDetails(Member $member)
    {
        $member->load(['country', 'branch', 'group']);
        
        return response()->json([
            'success' => true,
            'member' => $member
        ]);
    }

    /**
     * Approve a member
     */
    public function approve(Request $request, Member $member)
    {
        $request->validate([
            'approval_notes' => 'nullable|string|max:1000'
        ]);

        try {
            // CRITICAL: Check if registration fee has been paid before approval
            // NOTE: This only applies to NEW members being approved from today onwards.
            // Existing approved members are grandfathered in (no retroactive enforcement).
            $registrationFee = \App\Models\FeeType::active()
                                                  ->where(function($query) {
                                                      $query->where('name', 'like', '%registration%')
                                                            ->orWhere('name', 'like', '%Registration%');
                                                  })
                                                  ->first();

            if ($registrationFee) {
                // Check if member has paid registration fee
                $paidRegistrationFee = \App\Models\Fee::where('member_id', $member->id)
                                                      ->where('fees_type_id', $registrationFee->id)
                                                      ->where('status', 1) // Paid
                                                      ->first();

                if (!$paidRegistrationFee) {
                    return redirect()->back()
                                   ->with('error', 'Registration fee must be paid before member approval. Please record the registration fee payment first.');
                }
            }

            $member->approve(auth()->id(), $request->approval_notes);

            return redirect()->back()
                           ->with('success', 'Member ' . $member->fname . ' ' . $member->lname . ' has been approved successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Error approving member: ' . $e->getMessage());
        }
    }

    /**
     * Reject a member
     */
    public function reject(Request $request, Member $member)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);

        try {
            $member->reject(auth()->id(), $request->rejection_reason);

            return redirect()->back()
                           ->with('success', 'Member ' . $member->fname . ' ' . $member->lname . ' has been rejected.');

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Error rejecting member: ' . $e->getMessage());
        }
    }

    /**
     * Suspend a member
     */
    public function suspend(Request $request, Member $member)
    {
        $request->validate([
            'suspension_reason' => 'required|string|max:1000'
        ]);

        try {
            $member->suspend(auth()->id(), $request->suspension_reason);

            return redirect()->back()
                           ->with('success', 'Member ' . $member->fname . ' ' . $member->lname . ' has been suspended.');

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Error suspending member: ' . $e->getMessage());
        }
    }

    /**
     * Get pending members for approval
     */
    public function pending()
    {
        $pendingMembers = Member::with(['country', 'branch', 'group', 'addedBy'])
                               ->pending()
                               ->notDeleted()
                               ->orderBy('datecreated', 'desc')
                               ->paginate(20);

        $branches = Branch::active()->get();

        return view('admin.members.pending', compact('pendingMembers', 'branches'));
    }

    /**
     * Check for duplicate member identifiers via AJAX
     */
    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'field' => 'required|in:nin,contact,email',
            'value' => 'required|string',
            'member_id' => 'nullable|integer|exists:members,id'
        ]);

        $field = $request->field;
        $value = $request->value;
        $excludeId = $request->member_id;

        $exists = false;
        $existingMember = null;

        switch ($field) {
            case 'nin':
                $exists = Member::checkNinExists($value, $excludeId);
                if ($exists) {
                    $existingMember = Member::notDeleted()->where('nin', $value)->first();
                }
                break;
                
            case 'contact':
                $exists = Member::checkContactExists($value, $excludeId);
                if ($exists) {
                    $existingMember = Member::notDeleted()->where('contact', $value)->first();
                }
                break;
                
            case 'email':
                if (!empty($value)) {
                    $exists = Member::checkEmailExists($value, $excludeId);
                    if ($exists) {
                        $existingMember = Member::notDeleted()->where('email', $value)->first();
                    }
                }
                break;
        }

        return response()->json([
            'exists' => $exists,
            'member' => $exists ? [
                'id' => $existingMember->id,
                'name' => $existingMember->fname . ' ' . $existingMember->lname,
                'code' => $existingMember->code,
                'contact' => $existingMember->contact,
                'branch' => $existingMember->branch->name ?? 'Unknown'
            ] : null,
            'field_name' => match($field) {
                'nin' => 'National ID Number',
                'contact' => 'Contact Number',
                'email' => 'Email Address',
                default => ucfirst($field)
            }
        ]);
    }

    /**
     * Get member quick info for AJAX
     */
    public function quickInfo(Member $member)
    {
        $html = view('admin.fees.partials.member-info', compact('member'))->render();
        
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }

    /**
     * Get member loans for AJAX
     */
    public function getLoans(Member $member)
    {
        $loans = $member->loans()->where('status', '!=', 3)->get(); // Not closed loans
        
        return response()->json([
            'success' => true,
            'loans' => $loans->map(function($loan) {
                return [
                    'id' => $loan->id,
                    'amount' => $loan->amount,
                    'balance' => $loan->balance ?? $loan->amount,
                    'product' => $loan->product->name ?? 'Unknown Product'
                ];
            })
        ]);
    }

    /**
     * Get member recent fees for AJAX
     */
    public function getRecentFees(Member $member)
    {
        $fees = $member->fees()->with('feeType')->latest('datecreated')->take(5)->get();
        
        $html = view('admin.fees.partials.recent-fees', compact('fees'))->render();
        
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
}