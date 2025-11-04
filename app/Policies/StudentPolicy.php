<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StudentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // School users can view their own students
        return $user->user_type === 'school' && $user->school && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Student $student): bool
    {
        // Can only view students belonging to their school
        return $user->user_type === 'school' 
            && $user->school_id === $student->school_id
            && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only approved school users can create students
        return $user->user_type === 'school' && $user->school && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Student $student): bool
    {
        // Can only update students belonging to their school
        return $user->user_type === 'school' 
            && $user->school_id === $student->school_id
            && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Student $student): bool
    {
        // Can only delete students belonging to their school
        return $user->user_type === 'school' 
            && $user->school_id === $student->school_id
            && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Student $student): bool
    {
        // Can only restore students belonging to their school
        return $user->user_type === 'school' 
            && $user->school_id === $student->school_id
            && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Student $student): bool
    {
        // Can only force delete students belonging to their school
        return $user->user_type === 'school' 
            && $user->school_id === $student->school_id
            && $user->school->status === 'approved';
    }
}
