<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetType extends Model
{
    protected $table = 'asset_types';
    public $timestamps = false;
    protected $fillable = ['name'];

    public function assets()
    {
        return $this->hasMany(Asset::class, 'asset_type');
    }
}
