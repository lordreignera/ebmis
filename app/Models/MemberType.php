<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Get members of this type
     */
    public function members()
    {
        return $this->hasMany(Member::class, 'member_type');
    }

    /**
     * Scope to get only active member types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}