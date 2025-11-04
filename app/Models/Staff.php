<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'staff_id',
        'first_name',
        'last_name',
        'other_names',
        'gender',
        'date_of_birth',
        'nationality',
        'national_id',
        'religion',
        'phone_number',
        'email',
        'address',
        'district',
        'next_of_kin_name',
        'next_of_kin_phone',
        'staff_type',
        'position',
        'department',
        'subjects_taught',
        'date_joined',
        'employee_number',
        'employment_type',
        'highest_qualification',
        'institution_attended',
        'year_of_graduation',
        'certifications',
        'basic_salary',
        'allowances',
        'total_salary',
        'payment_frequency',
        'bank_name',
        'bank_account_number',
        'mobile_money_number',
        'cv_path',
        'certificate_path',
        'id_photo_path',
        'status',
        'termination_date',
        'termination_reason',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_joined' => 'date',
        'termination_date' => 'date',
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'total_salary' => 'decimal:2',
    ];

    /**
     * Boot function for model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($staff) {
            if (empty($staff->staff_id)) {
                $staff->staff_id = self::generateStaffId($staff->school_id);
            }
            // Calculate total salary
            $staff->total_salary = $staff->basic_salary + $staff->allowances;
        });

        static::updating(function ($staff) {
            // Recalculate total salary on update
            $staff->total_salary = $staff->basic_salary + $staff->allowances;
        });
    }

    /**
     * Generate unique staff ID.
     */
    public static function generateStaffId($schoolId)
    {
        $year = date('Y');
        $count = self::where('school_id', $schoolId)->whereYear('created_at', $year)->count() + 1;
        return 'STF' . $year . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the school.
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get classes where this staff is the class teacher.
     */
    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'class_teacher_id');
    }

    /**
     * Get full name.
     */
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->other_names} {$this->last_name}");
    }

    /**
     * Check if staff is teaching staff.
     */
    public function isTeacher()
    {
        return $this->staff_type === 'Teaching';
    }
}