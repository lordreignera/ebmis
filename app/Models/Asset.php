<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $table = 'assets';
    
    public $timestamps = false;

    protected $fillable = [
        'member_id', 'asset_id', 'asset_type',
        'business_id', 'quantity', 'value',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'value' => 'integer',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function assetType()
    {
        return $this->belongsTo(AssetType::class, 'asset_type');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function getTotalValueAttribute()
    {
        return $this->quantity * $this->value;
    }
}
