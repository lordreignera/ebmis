<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessAddress extends Model
{
    protected $table = 'business_address';
    
    public $timestamps = false;

    protected $fillable = [
        'business_id', 'street', 'plot_no', 'house_no', 'cell',
        'ward', 'division', 'district', 'country', 'tel_no',
        'mobile_no', 'fixed_line', 'email',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->plot_no, $this->street, $this->house_no,
            $this->cell, $this->ward, $this->division,
            $this->district, $this->country,
        ]);
        return implode(', ', $parts);
    }
}
