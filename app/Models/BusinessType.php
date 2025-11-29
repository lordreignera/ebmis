<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessType extends Model
{
    protected $table = 'business_type';
    public $timestamps = false;
    protected $fillable = ['name'];

    public function businesses()
    {
        return $this->hasMany(Business::class, 'b_type');
    }
}
