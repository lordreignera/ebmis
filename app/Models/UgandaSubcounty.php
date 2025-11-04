<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UgandaSubcounty extends Model
{
    use HasFactory;

    protected $fillable = [
        'district_id',
        'name',
        'type',
        'latitude',
        'longitude',
    ];

    /**
     * Get the district that owns the subcounty
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(UgandaDistrict::class, 'district_id');
    }

    /**
     * Get the parishes for the subcounty
     */
    public function parishes(): HasMany
    {
        return $this->hasMany(UgandaParish::class, 'subcounty_id');
    }
}
