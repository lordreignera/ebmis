<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HandlesLegacyTimestamps;

class Group extends Model
{
    use HasFactory, HandlesLegacyTimestamps;

    // Disable Laravel timestamps in favor of legacy datecreated
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'inception_date',
        'address',
        'sector',
        'type',
        'verified',
        'branch_id',
        'added_by',
        'datecreated'
    ];

    protected $casts = [
        'verified' => 'integer',
        'type' => 'integer',
        'inception_date' => 'date',
        'datecreated' => 'datetime',
    ];

    /**
     * Get the branch that owns the group
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who added this group
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get all members in this group (direct relationship via group_id)
     */
    public function members()
    {
        return $this->hasMany(Member::class);
    }

    /**
     * Get all loans for this group (through members)
     */
    public function loans()
    {
        return $this->hasManyThrough(\App\Models\PersonalLoan::class, \App\Models\Member::class);
    }

    /**
     * Get all group loans for this group
     */
    public function groupLoans()
    {
        return $this->hasMany(\App\Models\GroupLoan::class);
    }

    /**
     * Get all individual loans from group members
     */
    public function memberLoans()
    {
        return $this->hasManyThrough(\App\Models\PersonalLoan::class, \App\Models\Member::class);
    }

    /**
     * Get all savings for this group (through members)
     */
    public function savings()
    {
        return $this->hasManyThrough(Saving::class, Member::class);
    }

    /**
     * Get all savings from group members
     */
    public function memberSavings()
    {
        return $this->hasManyThrough(Saving::class, Member::class);
    }

    /**
     * Get group type name
     */
    public function getTypeNameAttribute()
    {
        $types = [
            1 => 'Open Group',
            2 => 'Closed Group'
        ];

        return $types[$this->type] ?? 'Unknown';
    }

    /**
     * Get total members count
     */
    public function getTotalMembersAttribute()
    {
        return $this->members()->notDeleted()->count();
    }

    /**
     * Get active members count
     */
    public function getActiveMembersAttribute()
    {
        return $this->members()->verified()->notDeleted()->count();
    }

    /**
     * Get total loans value for group members
     */
    public function getTotalLoansValueAttribute()
    {
        return $this->memberLoans()->sum('principal');
    }

    /**
     * Get total savings value for group members
     */
    public function getTotalSavingsValueAttribute()
    {
        return $this->memberSavings()->sum('amount');
    }

    /**
     * Scope for verified groups
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', 1);
    }

    /**
     * Scope for open groups
     */
    public function scopeOpen($query)
    {
        return $query->where('type', 1);
    }

    /**
     * Scope for closed groups
     */
    public function scopeClosed($query)
    {
        return $query->where('type', 2);
    }

    // ===============================================================
    // GROUP LOAN BUSINESS RULES
    // ===============================================================

    /**
     * Maximum members allowed in a group
     */
    const MAX_MEMBERS = 5;

    /**
     * Minimum members required for group loan approval
     */
    const MIN_MEMBERS_FOR_LOAN = 3;

    /**
     * Check if group can accept new members
     */
    public function canAcceptNewMembers(): bool
    {
        return $this->total_members < self::MAX_MEMBERS;
    }

    /**
     * Get remaining member slots
     */
    public function getRemainingSlots(): int
    {
        return max(0, self::MAX_MEMBERS - $this->total_members);
    }

    /**
     * Check if group is eligible for group loans
     */
    public function isEligibleForGroupLoan(): bool
    {
        return $this->verified && 
               $this->active_members >= self::MIN_MEMBERS_FOR_LOAN &&
               $this->allMembersApproved();
    }

    /**
     * Check if all members are approved and fee-paid
     */
    public function allMembersApproved(): bool
    {
        $totalMembers = $this->members()->notDeleted()->count();
        $approvedMembers = $this->members()->notDeleted()->approved()->count();
        
        return $totalMembers > 0 && $totalMembers === $approvedMembers;
    }

    /**
     * Get approved members only
     */
    public function approvedMembers()
    {
        return $this->members()->approved()->notDeleted();
    }

    /**
     * Get pending members
     */
    public function pendingMembers()
    {
        return $this->members()->pending()->notDeleted();
    }

    /**
     * Get group loan eligibility status with details
     */
    public function getLoanEligibilityStatus(): array
    {
        $status = [
            'eligible' => false,
            'reasons' => []
        ];

        if (!$this->verified) {
            $status['reasons'][] = 'Group is not verified';
        }

        if ($this->total_members < self::MIN_MEMBERS_FOR_LOAN) {
            $status['reasons'][] = "Group needs at least " . self::MIN_MEMBERS_FOR_LOAN . " members (currently has {$this->total_members})";
        }

        if (!$this->allMembersApproved()) {
            $pendingCount = $this->pendingMembers()->count();
            $status['reasons'][] = "All members must be approved ({$pendingCount} members still pending)";
        }

        $status['eligible'] = empty($status['reasons']);

        return $status;
    }

    /**
     * Add member to group with validation
     */
    public function addMember(Member $member): array
    {
        $result = [
            'success' => false,
            'message' => ''
        ];

        // Check if group can accept new members
        if (!$this->canAcceptNewMembers()) {
            $result['message'] = "Group has reached maximum capacity of " . self::MAX_MEMBERS . " members";
            return $result;
        }

        // Check if member is approved
        if (!$member->isApproved()) {
            $result['message'] = "Member must be approved before joining a group";
            return $result;
        }

        // Check if member is not already in another group
        if ($member->group_id && $member->group_id !== $this->id) {
            $result['message'] = "Member is already assigned to another group";
            return $result;
        }

        // Assign member to group
        $member->update(['group_id' => $this->id]);

        $result['success'] = true;
        $result['message'] = "Member successfully added to group";

        return $result;
    }

    /**
     * Remove member from group
     */
    public function removeMember(Member $member): array
    {
        $result = [
            'success' => false,
            'message' => ''
        ];

        // Check if member is in this group (handle both null and 0)
        if ($member->group_id != $this->id) {
            $result['message'] = "Member is not in this group (Member group_id: {$member->group_id}, Group id: {$this->id})";
            return $result;
        }

        // Skip group loans check for now - can be added later
        // Check if member has active group loans
        // $hasActiveGroupLoans = $this->groupLoans()
        //                             ->whereHas('members', function($query) use ($member) {
        //                                 $query->where('member_id', $member->id);
        //                             })
        //                             ->whereIn('status', ['active', 'pending'])
        //                             ->exists();

        // if ($hasActiveGroupLoans) {
        //     $result['message'] = "Cannot remove member with active group loans";
        //     return $result;
        // }

        // Remove member from group
        $member->group_id = null;
        $member->save();

        $result['success'] = true;
        $result['message'] = "Member successfully removed from group";

        return $result;
    }

    /**
     * Get the user who approved this group
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}