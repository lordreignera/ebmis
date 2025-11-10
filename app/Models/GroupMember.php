<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'member_id',
        'status',
        'added_by'
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    /**
     * Get the group that owns this member relationship
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the member in this group relationship
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the user who added this member to the group
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}