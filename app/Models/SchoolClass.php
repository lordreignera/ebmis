<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'class_name',
        'class_code',
        'level',
        'stream',
        'class_teacher_id',
        'capacity',
        'current_enrollment',
        'description',
        'academic_year',
        'status',
    ];

    /**
     * Get the school that owns the class.
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the class teacher.
     */
    public function classTeacher()
    {
        return $this->belongsTo(Staff::class, 'class_teacher_id');
    }

    /**
     * Get the students in this class.
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /**
     * Check if class is at capacity.
     */
    public function isAtCapacity()
    {
        return $this->current_enrollment >= $this->capacity;
    }

    /**
     * Get available slots.
     */
    public function getAvailableSlotsAttribute()
    {
        return max(0, $this->capacity - $this->current_enrollment);
    }
}