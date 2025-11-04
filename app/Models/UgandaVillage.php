<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UgandaVillage extends Model
{
    use HasFactory;

    protected $fillable = [
        'parish_id',
        'name',
        'latitude',
        'longitude',
    ];

    /**
     * Get the parish that owns the village
     */
    public function parish(): BelongsTo
    {
        return $this->belongsTo(UgandaParish::class, 'parish_id');
    }
}
