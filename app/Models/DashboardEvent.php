<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'event_date',
        'event_time',
        'category',
        'notes',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
