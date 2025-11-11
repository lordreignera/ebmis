<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'class_id',
        'student_id',
        'first_name',
        'last_name',
        'other_names',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'nationality',
        'religion',
        'address',
        'district',
        'village',
        'phone_number',
        'email',
        'parent_name',
        'parent_phone',
        'parent_email',
        'parent_occupation',
        'parent_address',
        'relationship',
        'emergency_contact_name',
        'emergency_contact_phone',
        'admission_number',
        'admission_date',
        'previous_school',
        'academic_year',
        'boarding_status',
        'blood_group',
        'allergies',
        'medical_conditions',
        'photo_path',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
    ];

    /**
     * Boot function for model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($student) {
            if (empty($student->student_id)) {
                $student->student_id = self::generateStudentId($student->school_id);
            }
        });
    }

    /**
     * Generate unique student ID.
     */
    public static function generateStudentId($schoolId)
    {
        $year = date('Y');
        $count = self::where('school_id', $schoolId)->whereYear('created_at', $year)->count() + 1;
        return 'STU' . $year . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the school.
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the class.
     */
    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get student loans.
     */
    public function studentLoans()
    {
        return $this->hasMany(StudentLoan::class);
    }

    /**
     * Get full name.
     */
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->other_names} {$this->last_name}");
    }

    /**
     * Get age.
     */
    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }
}