<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Member;
use App\Models\Branch;
use App\Models\Loan;
use App\Models\Saving;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GroupController extends Controller
{
    /**
     * Display a listing of groups
     */
    public function index(Request $request)
    {
        $query = Group::with(['branch', 'addedBy']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by branch
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        $groups = $query->withCount(['members'])
                       ->orderByLegacyTimestamp()
                       ->paginate(20);

        $branches = Branch::active()->get();

        // Calculate statistics
        $stats = [
            'total_groups' => Group::count(),
            'active_groups' => Group::where('status', 'active')->count(),
            'total_members' => Member::whereNotNull('group_id')->count(),
            'average_group_size' => Group::withCount('members')->get()->avg('members_count') ?? 0
        ];

        return view('admin.groups.index', compact('groups', 'branches', 'stats'));
    }

    /**
     * Show the form for creating a new group
     */
    public function create()
    {
        $branches = Branch::active()->get();
        $members = Member::verified()->notDeleted()->whereNull('group_id')->get();
        
        return view('admin.groups.create', compact('branches', 'members'));
    }

    /**
     * Store a newly created group
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'inception_date' => 'required|date',
            'village' => 'nullable|string|max:255',
            'parish' => 'nullable|string|max:255',
            'subcounty' => 'nullable|string|max:255',
            'county' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'sector' => 'required|string|max:80',
            'type' => 'required|integer|in:1,2', // 1=open, 2=closed
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:members,id',
        ]);

        try {
            DB::beginTransaction();

            // Map fields to match original database structure
            $groupData = [
                'code' => 'BIMS' . time(),
                'name' => $validated['name'],
                'inception_date' => $validated['inception_date'],
                'address' => implode(', ', array_filter([
                    $validated['village'],
                    $validated['parish'],
                    $validated['subcounty'],
                    $validated['county'],
                    $validated['district'],
                    $validated['region']
                ])),
                'sector' => $validated['sector'],
                'type' => $validated['type'],
                'verified' => 0,
                'branch_id' => $validated['branch_id'],
                'added_by' => auth()->id(),
                'datecreated' => now()
            ];
            
            $group = Group::create($groupData);

            // Assign selected members to the group
            if (!empty($validated['member_ids'])) {
                Member::whereIn('id', $validated['member_ids'])
                      ->whereNull('group_id') // Extra safety check
                      ->update(['group_id' => $group->id]);
            }

            DB::commit();

            return redirect()->route('admin.groups.show', $group)
                            ->with('success', 'Group created successfully and is pending approval.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error creating group: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified group
     */
    public function show(Group $group)
    {
        $group->load([
            'branch',
            'addedBy',
            'members.member',
            'loans.member',
            'savings.member'
        ]);

        // Get group statistics
        $stats = [
            'total_members' => $group->members()->count(),
            'total_loans' => $group->loans()->count(),
            'active_loans' => $group->loans()->where('status', 2)->count(),
            'total_disbursed' => $group->loans()->where('status', 2)->sum('principal'),
            'total_savings' => $group->savings()->sum('balance'),
            // 'meetings_held' => $group->meetings()->count(),
            // 'last_meeting' => $group->meetings()->latest()->first(),
        ];

        return view('admin.groups.show', compact('group', 'stats'));
    }

    /**
     * Show the form for editing the specified group
     */
    public function edit(Group $group)
    {
        $branches = Branch::active()->get();
        $members = Member::verified()->notDeleted()->get();
        $groupMembers = $group->members;
        $availableMembers = Member::verified()->notDeleted()
            ->whereNull('group_id')
            ->get();
        
        return view('admin.groups.edit', compact('group', 'branches', 'members', 'groupMembers', 'availableMembers'));
    }

    /**
     * Update the specified group
     */
    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'branch_id' => 'required|exists:branches,id',
            'inception_date' => 'required|date',
            'village' => 'nullable|string|max:255',
            'parish' => 'nullable|string|max:255',
            'subcounty' => 'nullable|string|max:255',
            'county' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'meeting_frequency' => 'required|integer|min:1',
            'meeting_day' => 'required|integer|min:1|max:7',
            'meeting_time' => 'required|string',
            'meeting_venue' => 'nullable|string|max:255',
            'status' => 'required|integer|in:0,1',
        ]);

        $group->update($validated);

        return redirect()->route('admin.groups.show', $group)
                        ->with('success', 'Group updated successfully.');
    }

    /**
     * Remove the specified group
     */
    public function destroy(Group $group)
    {
        // Check if group has active loans or savings
        $activeLoans = $group->loans()->whereIn('status', [0, 1, 2])->count();
        $activeSavings = $group->savings()->where('status', 1)->count();

        if ($activeLoans > 0 || $activeSavings > 0) {
            return redirect()->back()
                            ->with('error', 'Cannot delete group. It has active loans or savings accounts.');
        }

        try {
            DB::beginTransaction();

            // Remove all group members by setting their group_id to null
            $group->members()->update(['group_id' => null]);
            
            // Remove all group meetings (commented out until Meeting model is created)
            // $group->meetings()->delete();

            $group->delete();

            DB::commit();

            return redirect()->route('admin.groups.index')
                            ->with('success', 'Group deleted successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                            ->with('error', 'Error deleting group: ' . $e->getMessage());
        }
    }

    /**
     * Add member to group
     */
    public function addMember(Request $request, Group $group)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
        ]);

        $member = Member::findOrFail($request->member_id);

        // Use the enhanced business logic from Group model
        $result = $group->addMember($member);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        } else {
            return redirect()->back()->with('error', $result['message']);
        }
    }

    /**
     * Remove member from group
     */
    public function removeMember(Group $group, Member $member)
    {
        // Use the enhanced business logic from Group model
        $result = $group->removeMember($member);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        } else {
            return redirect()->back()->with('error', $result['message']);
        }
    }

    /**
     * Record group meeting
     */
    public function recordMeeting(Request $request, Group $group)
    {
        $validated = $request->validate([
            'meeting_date' => 'required|date',
            'attendees' => 'required|array',
            'attendees.*' => 'exists:members,id',
            'agenda' => 'nullable|string',
            'notes' => 'nullable|string',
            'venue' => 'nullable|string|max:255',
        ]);

        $validated['group_id'] = $group->id;
        $validated['added_by'] = auth()->id();
        $validated['total_attendees'] = count($validated['attendees']);

        $meeting = GroupMeeting::create($validated);

        // Record attendance
        foreach ($validated['attendees'] as $memberId) {
            DB::table('group_meeting_attendance')->insert([
                'meeting_id' => $meeting->id,
                'member_id' => $memberId,
                'present' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return redirect()->back()->with('success', 'Meeting recorded successfully.');
    }

    /**
     * Get group members for AJAX
     */
    public function getMembers(Group $group)
    {
        $members = $group->members()
                        ->where('status', 1)
                        ->with('member:id,fname,lname,code')
                        ->get()
                        ->map(function($groupMember) {
                            return [
                                'id' => $groupMember->member->id,
                                'name' => $groupMember->member->fname . ' ' . $groupMember->member->lname,
                                'code' => $groupMember->member->code,
                            ];
                        });

        return response()->json([
            'success' => true,
            'members' => $members
        ]);
    }

    /**
     * Toggle group status
     */
    public function toggleStatus(Group $group)
    {
        $group->update([
            'status' => $group->status === 1 ? 0 : 1
        ]);

        $status = $group->status === 1 ? 'activated' : 'deactivated';

        return redirect()->back()
                        ->with('success', "Group {$status} successfully.");
    }

    /**
     * Get available members for adding to group (AJAX)
     */
    public function getAvailableMembers(Group $group, Request $request)
    {
        $search = $request->get('search', '');
        
        // Get approved members who are not in any group or are in this group
        $availableMembers = Member::approved()
                                 ->notDeleted()
                                 ->where(function($query) use ($group) {
                                     $query->whereNull('group_id')
                                           ->orWhere('group_id', $group->id);
                                 })
                                 ->when($search, function($query) use ($search) {
                                     $query->where(function($q) use ($search) {
                                         $q->where('fname', 'like', "%{$search}%")
                                           ->orWhere('lname', 'like', "%{$search}%")
                                           ->orWhere('code', 'like', "%{$search}%")
                                           ->orWhere('contact', 'like', "%{$search}%");
                                     });
                                 })
                                 ->with(['branch'])
                                 ->orderBy('fname')
                                 ->limit(50)
                                 ->get();

        return response()->json([
            'members' => $availableMembers->map(function($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->fname . ' ' . $member->lname,
                    'code' => $member->code,
                    'contact' => $member->contact,
                    'branch' => $member->branch->name ?? 'Unknown',
                    'status' => $member->status,
                    'in_group' => $member->group_id ? true : false
                ];
            }),
            'group_capacity' => [
                'current' => $group->total_members,
                'max' => Group::MAX_MEMBERS,
                'remaining' => $group->getRemainingSlots(),
                'can_add' => $group->canAcceptNewMembers()
            ]
        ]);
    }

    /**
     * Get group loan eligibility status (AJAX)
     */
    public function checkLoanEligibility(Group $group)
    {
        $eligibility = $group->getLoanEligibilityStatus();
        
        return response()->json([
            'eligible' => $eligibility['eligible'],
            'reasons' => $eligibility['reasons'],
            'member_info' => [
                'total' => $group->total_members,
                'approved' => $group->active_members,
                'pending' => $group->pendingMembers()->count(),
                'min_required' => Group::MIN_MEMBERS_FOR_LOAN,
                'max_allowed' => Group::MAX_MEMBERS
            ]
        ]);
    }

    /**
     * Approve a pending group
     */
    public function approve(Group $group)
    {
        try {
            if ($group->status !== 'pending') {
                return redirect()->back()->with('error', 'Only pending groups can be approved.');
            }

            $group->update([
                'status' => 'active',
                'approved_at' => now(),
                'approved_by' => auth()->id()
            ]);

            return redirect()->back()->with('success', 'Group approved successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to approve group: ' . $e->getMessage());
        }
    }

    /**
     * Show group members management page
     */
    public function members(Group $group)
    {
        $group->load(['members.branch', 'branch']);
        $availableMembers = Member::verified()
                                  ->notDeleted()
                                  ->whereNull('group_id')
                                  ->with(['branch'])
                                  ->orderBy('fname')
                                  ->get();

        return view('admin.groups.members', compact('group', 'availableMembers'));
    }
}