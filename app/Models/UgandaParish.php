<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UgandaParish extends Model
{
    use HasFactory;

    protected $fillable = [
        'subcounty_id',
        'name',
        'latitude',
        'longitude',
    ];

    /**
     * Get the subcounty that owns the parish
     */
    public function subcounty(): BelongsTo
    {
        return $this->belongsTo(UgandaSubcounty::class, 'subcounty_id');
    }

    /**
     * Get the villages for the parish
     */
    public function villages(): HasMany
    {
        return $this->hasMany(UgandaVillage::class, 'parish_id');
    }
}
