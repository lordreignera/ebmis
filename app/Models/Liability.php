<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Liability extends Model
{
    protected $table = 'liabilities';
    
    public $timestamps = false;

    protected $fillable = [
        'member_id', 'liability_id', 'liability_type',
        'business_id', 'value',
    ];

    protected $casts = [
        'value' => 'integer',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function liabilityType()
    {
        return $this->belongsTo(LiabilityType::class, 'liability_type');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
