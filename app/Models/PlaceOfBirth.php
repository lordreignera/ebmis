<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceOfBirth extends Model
{
    use HasFactory;

    protected $table = 'place_of_birth';
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'plot_no',
        'village',
        'parish',
        'subcounty',
        'county',
        'country_id'
    ];

    /**
     * Get the member that owns this place of birth
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the country
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}