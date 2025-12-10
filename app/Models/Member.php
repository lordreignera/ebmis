<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use App\Traits\HandlesLegacyTimestamps;
use App\Traits\EastAfricanTime;
use App\Models\Fee;

class Member extends Model
{
    use HasFactory, HandlesLegacyTimestamps, EastAfricanTime;

    protected $fillable = [
        'code',
        'fname',
        'lname',
        'mname',
        'nin',
        'contact',
        'alt_contact',
        'email',
        'plot_no',
        'village',
        'parish',
        'subcounty',
        'county',
        'country_id',
        'gender',
        'dob',
        'fixed_line',
        'verified',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
        'comments',
        'member_type',
        'group_id',
        'branch_id',
        'pp_file',
        'id_file',
        'soft_delete',
        'del_user',
        'del_comments',
        'added_by',
        'password',
        'mobile_pin',
        'datecreated' // Old database column
    ];

    protected $casts = [
        'verified' => 'boolean',
        'soft_delete' => 'boolean',
        'dob' => 'date',
        'approved_at' => 'datetime',
        'datecreated' => 'datetime', // Cast old column to datetime
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the country that owns the member
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the branch that owns the member
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the group that owns the member (if any)
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user who added this member
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get the member type
     */
    public function memberType()
    {
        return $this->belongsTo(MemberType::class, 'member_type');
    }

    /**
     * Get all personal loans for this member
     */
    public function personalLoans()
    {
        return $this->hasMany(\App\Models\PersonalLoan::class);
    }

    /**
     * Get all loans for this member (personal loans + group loans they're involved in)
     */
    public function loans()
    {
        return $this->personalLoans();
    }

    /**
     * Get all savings accounts for this member
     */
    public function savings()
    {
        return $this->hasMany(\App\Models\Saving::class);
    }

    /**
     * Get all fees for this member
     */
    public function fees()
    {
        return $this->hasMany(Fee::class)->orderBy('datecreated', 'desc');
    }

    /**
     * Get the place of birth for this member
     */
    public function placeOfBirth()
    {
        return $this->hasOne(PlaceOfBirth::class);
    }

    /**
     * Get all businesses for this member
     */
    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    /**
     * Get all assets for this member
     */
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get all liabilities for this member
     */
    public function liabilities()
    {
        return $this->hasMany(Liability::class);
    }

    /**
     * Get all documents for this member
     */
    public function documents()
    {
        return $this->hasMany(MemberDocument::class);
    }

    /**
     * Get attachment library (migrated from old system)
     * Identified by document_type = 'other' which was set by migration script
     */
    public function attachmentLibrary()
    {
        return $this->hasMany(MemberDocument::class)
            ->where('document_type', 'other')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get total assets value
     */
    public function getTotalAssetsAttribute()
    {
        return (int) $this->assets()->sum(\DB::raw('quantity * value'));
    }

    /**
     * Get total liabilities value
     */
    public function getTotalLiabilitiesAttribute()
    {
        return (int) $this->liabilities()->sum('value');
    }

    /**
     * Get net worth (assets - liabilities)
     */
    public function getNetWorthAttribute()
    {
        return (int) ($this->total_assets - $this->total_liabilities);
    }

    /**
     * Get all guarantees provided by this member
     */
    public function guarantees()
    {
        return $this->hasMany(Guarantor::class, 'member_id');
    }

    /**
     * Get all guarantors for this member's personal loans
     */
    public function personalLoanGuarantors()
    {
        return $this->hasManyThrough(Guarantor::class, PersonalLoan::class);
    }

    /**
     * Get member's full name
     */
    public function getFullNameAttribute()
    {
        return trim($this->fname . ' ' . $this->mname . ' ' . $this->lname);
    }

    /**
     * Get member photo URL
     */
    public function getPpFileUrlAttribute()
    {
        return $this->pp_file ? \App\Services\FileStorageService::getFileUrl($this->pp_file) : null;
    }

    /**
     * Get member ID file URL
     */
    public function getIdFileUrlAttribute()
    {
        return $this->id_file ? \App\Services\FileStorageService::getFileUrl($this->id_file) : null;
    }

    /**
     * Get member's full address
     */
    public function getFullAddressAttribute()
    {
        $address = [];
        if ($this->plot_no) $address[] = $this->plot_no;
        if ($this->village) $address[] = $this->village;
        if ($this->parish) $address[] = $this->parish;
        if ($this->subcounty) $address[] = $this->subcounty;
        if ($this->county) $address[] = $this->county;
        
        return implode(', ', $address);
    }

    /**
     * Get created_at attribute - fallback to datecreated for old data
     */
    public function getCreatedAtAttribute($value)
    {
        // If created_at exists, use it
        if ($value) {
            return $this->asDateTime($value);
        }
        
        // Otherwise, fallback to datecreated for old data
        if ($this->attributes['datecreated'] ?? null) {
            return $this->asDateTime($this->attributes['datecreated']);
        }
        
        return null;
    }

    /**
     * Get updated_at attribute - fallback to datecreated for old data
     */
    public function getUpdatedAtAttribute($value)
    {
        // If updated_at exists, use it
        if ($value) {
            return $this->asDateTime($value);
        }
        
        // Otherwise, fallback to datecreated for old data
        if ($this->attributes['datecreated'] ?? null) {
            return $this->asDateTime($this->attributes['datecreated']);
        }
        
        return null;
    }

    /**
     * Scope for verified members
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Scope for individual members
     * member_type = 1 (matches member_types table)
     */
    public function scopeIndividual($query)
    {
        return $query->where('member_type', 1); // Individual = ID 1 per member_types table
    }

    /**
     * Scope for group members
     * member_type = 2 (matches member_types table)
     */
    public function scopeGroupMember($query)
    {
        return $query->where('member_type', 2); // Group = ID 2 per member_types table
    }

    /**
     * Scope for non-deleted members
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('soft_delete', false);
    }

    // ===============================================================
    // STATUS RELATIONSHIPS & METHODS
    // ===============================================================
    
    /**
     * Get the user who approved this member
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if the table has the new status column
     */
    private static function hasStatusColumn(): bool
    {
        static $hasStatus = null;
        
        if ($hasStatus === null) {
            $hasStatus = Schema::hasColumn('members', 'status');
        }
        
        return $hasStatus;
    }

    /**
     * Scope for pending members
     */
    public function scopePending($query)
    {
        // For backward compatibility: check if status column exists
        if (self::hasStatusColumn()) {
            return $query->where('status', 'pending');
        } else {
            // For old database, pending members are unverified (verified = 0) and not deleted
            return $query->where('verified', 0)->where('soft_delete', 0);
        }
    }

    /**
     * Scope for approved members
     */
    public function scopeApproved($query)
    {
        // For backward compatibility: check if status column exists
        if (self::hasStatusColumn()) {
            return $query->where('status', 'approved');
        } else {
            // Fall back to verified column for old database
            return $query->where('verified', 1);
        }
    }

    /**
     * Scope for suspended members
     */
    public function scopeSuspended($query)
    {
        // For backward compatibility: check if status column exists
        if (self::hasStatusColumn()) {
            return $query->where('status', 'suspended');
        } else {
            // For old database, suspended members don't have a direct equivalent
            // We can consider soft_delete = 1 as suspended for compatibility
            return $query->where('soft_delete', 1);
        }
    }

    /**
     * Scope for rejected members
     */
    public function scopeRejected($query)
    {
        // For backward compatibility: check if status column exists
        if (self::hasStatusColumn()) {
            return $query->where('status', 'rejected');
        } else {
            // For old database, rejected members might be unverified (verified = 0)
            // But we need to distinguish from pending, so we'll check soft_delete = 0 and verified = 0
            return $query->where('verified', 0)->where('soft_delete', 0);
        }
    }

    /**
     * Check if member is pending approval
     */
    public function isPending(): bool
    {
        // For backward compatibility: check if status column exists
        if (self::hasStatusColumn()) {
            return $this->status === 'pending';
        } else {
            // For old database, pending means unverified and not deleted
            return $this->verified == 0 && $this->soft_delete == 0;
        }
    }

    /**
     * Check if member is approved
     */
    public function isApproved(): bool
    {
        // For backward compatibility: check if status column exists
        if (self::hasStatusColumn()) {
            return $this->status === 'approved';
        } else {
            // For old database, approved means verified
            return $this->verified == 1;
        }
    }

    /**
     * Check if member is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if member is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve member
     */
    public function approve($approvedBy = null, $notes = null): bool
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy ?? auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $notes,
            'verified' => true, // Also mark as verified when approved
        ]);

        return true;
    }

    /**
     * Reject member
     */
    public function reject($rejectedBy = null, $reason = null): bool
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $rejectedBy ?? auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Suspend member
     */
    public function suspend($suspendedBy = null, $reason = null): bool
    {
        $this->update([
            'status' => 'suspended',
            'approved_by' => $suspendedBy ?? auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Get member status (compatible with old database)
     */
    public function getStatusAttribute()
    {
        // Check if status column exists in the database
        if (self::hasStatusColumn() && isset($this->attributes['status'])) {
            return $this->attributes['status'];
        }
        
        // For old database, derive status from verified and soft_delete columns
        if ($this->soft_delete == 1) {
            return 'suspended';
        } elseif ($this->verified == 1) {
            return 'approved';
        } else {
            return 'pending';
        }
    }

    /**
     * Get status badge class for display
     */
    public function getStatusBadgeAttribute(): string
    {
        switch ($this->status) {
            case 'pending':
                return 'bg-warning text-dark';
            case 'approved':
                return 'bg-success';
            case 'suspended':
                return 'bg-secondary';
            case 'rejected':
                return 'bg-danger';
            default:
                return 'bg-dark';
        }
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        switch ($this->status) {
            case 'pending':
                return 'Pending Approval';
            case 'approved':
                return 'Approved';
            case 'suspended':
                return 'Suspended';
            case 'rejected':
                return 'Rejected';
            default:
                return 'Unknown';
        }
    }

    /**
     * Check if member can apply for loans
     */
    public function canApplyForLoans(): bool
    {
        return $this->isApproved() && $this->verified && !$this->hasActiveLoan();
    }

    /**
     * Check if member has any active loans (loans with outstanding repayment schedules)
     */
    public function hasActiveLoan(): bool
    {
        return $this->loans()
                    ->whereIn('status', [1, 2]) // Approved or Disbursed
                    ->whereHas('schedules', function($query) {
                        $query->where('status', 0); // Unpaid schedules
                    })
                    ->exists();
    }

    /**
     * Get member's active loans
     */
    public function activeLoans()
    {
        return $this->loans()
                    ->whereIn('status', [1, 2]) // Approved or Disbursed
                    ->whereHas('schedules', function($query) {
                        $query->where('status', 0); // Unpaid schedules
                    });
    }

    // ===============================================================
    // DUPLICATE PREVENTION METHODS
    // ===============================================================

    /**
     * Check if NIN already exists (excluding soft deleted and optionally current member)
     */
    public static function checkNinExists(string $nin, ?int $excludeId = null): bool
    {
        $query = self::notDeleted()->where('nin', $nin);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Check if contact number already exists (excluding soft deleted and optionally current member)
     */
    public static function checkContactExists(string $contact, ?int $excludeId = null): bool
    {
        $query = self::notDeleted()->where('contact', $contact);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Check if email already exists (excluding soft deleted and optionally current member)
     */
    public static function checkEmailExists(string $email, ?int $excludeId = null): bool
    {
        if (empty($email)) {
            return false; // Email is optional
        }
        
        $query = self::notDeleted()->where('email', $email);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Find existing member by identifying information
     */
    public static function findExistingMember(string $nin, string $contact, ?string $email = null): ?Member
    {
        return self::notDeleted()
                   ->where(function($query) use ($nin, $contact, $email) {
                       $query->where('nin', $nin)
                             ->orWhere('contact', $contact);
                       
                       if ($email) {
                           $query->orWhere('email', $email);
                       }
                   })
                   ->first();
    }

    /**
     * Get all duplicate conflicts for a member's data
     */
    public static function getDuplicateConflicts(string $nin, string $contact, ?string $email = null, ?int $excludeId = null): array
    {
        $conflicts = [];
        
        // Check NIN
        if (self::checkNinExists($nin, $excludeId)) {
            $member = self::notDeleted()->where('nin', $nin)->first();
            $conflicts['nin'] = [
                'field' => 'National ID Number',
                'value' => $nin,
                'existing_member' => $member
            ];
        }
        
        // Check Contact
        if (self::checkContactExists($contact, $excludeId)) {
            $member = self::notDeleted()->where('contact', $contact)->first();
            $conflicts['contact'] = [
                'field' => 'Contact Number',
                'value' => $contact,
                'existing_member' => $member
            ];
        }
        
        // Check Email (if provided)
        if ($email && self::checkEmailExists($email, $excludeId)) {
            $member = self::notDeleted()->where('email', $email)->first();
            $conflicts['email'] = [
                'field' => 'Email Address',
                'value' => $email,
                'existing_member' => $member
            ];
        }
        
        return $conflicts;
    }
}