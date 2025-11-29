<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $table = 'business';
    
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'name',
        'reg_date',
        'reg_no',
        'tin',
        'b_type',
        'pdt_1',
        'pdt_2',
        'pdt_3',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class, 'b_type');
    }

    public function address()
    {
        return $this->hasOne(BusinessAddress::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'business_id');
    }

    public function liabilities()
    {
        return $this->hasMany(Liability::class, 'business_id');
    }
}
