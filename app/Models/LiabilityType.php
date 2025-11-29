<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiabilityType extends Model
{
    protected $table = 'liability_types';
    public $timestamps = false;
    protected $fillable = ['name'];

    public function liabilities()
    {
        return $this->hasMany(Liability::class, 'liability_type');
    }
}
