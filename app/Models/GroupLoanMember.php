<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupLoanMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'group_id',
        'member_id',
        'principal',
        'added_by'
    ];

    protected $casts = [
        'principal' => 'decimal:2',
    ];

    /**
     * Get the group loan that owns this member allocation
     */
    public function groupLoan()
    {
        return $this->belongsTo(GroupLoan::class, 'loan_id');
    }

    /**
     * Get the group
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the member
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the user who added this allocation
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get member's share percentage
     */
    public function getSharePercentageAttribute()
    {
        $totalLoanAmount = $this->groupLoan->principal;
        if ($totalLoanAmount > 0) {
            return ($this->principal / $totalLoanAmount) * 100;
        }
        return 0;
    }
}