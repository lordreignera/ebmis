<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'school_id',
        'branch_id',
        'region_id',
        'status',
        'approved_by',
        'approved_at',
        'phone',
        'address',
        'designation',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'approved_at' => 'datetime',
        ];
    }

    // ===============================================================
    // RELATIONSHIPS
    // ===============================================================
    
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ===============================================================
    // SCOPES
    // ===============================================================
    
    public function scopeSuperAdmin($query)
    {
        return $query->where('user_type', 'super_admin');
    }

    public function scopeSchoolUsers($query)
    {
        return $query->where('user_type', 'school');
    }

    public function scopeBranchUsers($query)
    {
        return $query->where('user_type', 'branch');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ===============================================================
    // APPROVAL STATUS METHODS
    // ===============================================================
    
    /**
     * Check if user is approved and can login
     */
    public function isApproved(): bool
    {
        // For school users, check if their school is approved
        if ($this->user_type === 'school') {
            // User must be active AND have a school AND school must be approved
            return $this->status === 'active' 
                && $this->school 
                && $this->school->status === 'approved';
        }
        
        // For other user types, just check active status
        return $this->status === 'active';
    }

    /**
     * Check if user is pending approval
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if user is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if user is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Get user status message for login attempts
     */
    public function getStatusMessage(): string
    {
        if ($this->user_type === 'school') {
            // Check if school record exists
            if (!$this->school) {
                return 'School record not found. Please contact the administrator.';
            }
            
            // Check school approval status
            return match($this->school->status) {
                'pending' => 'Your school application is pending approval. You will be notified via email once approved.',
                'suspended' => 'Your school account has been suspended. Please contact the administrator for more information.',
                'rejected' => 'Your school application was rejected. Please contact the administrator if you believe this is an error.',
                'approved' => $this->status === 'active' 
                    ? 'Your account is active and ready to use.' 
                    : 'Your school is approved but your user account is not active. Please contact the administrator.',
                default => 'School status unknown. Please contact the administrator.'
            };
        }
        
        return match($this->status) {
            'pending' => 'Your account is pending approval. Please contact the administrator.',
            'suspended' => 'Your account has been suspended. Please contact the administrator.',
            'rejected' => 'Your account application was rejected. Please contact the administrator.',
            'active' => 'Account is active.',
            default => 'Account status unknown. Please contact the administrator.'
        };
    }

    /**
     * Approve user account
     */
    public function approve($approvedBy = null): bool
    {
        $this->update([
            'status' => 'active',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        return true;
    }

    /**
     * Reject user account
     */
    public function reject($rejectedBy = null): bool
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
        ]);

        return true;
    }
}
