<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SchoolClassPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // School users can view their own classes
        return $user->user_type === 'school' && $user->school && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SchoolClass $schoolClass): bool
    {
        // Can only view classes belonging to their school
        return $user->user_type === 'school' 
            && $user->school_id === $schoolClass->school_id
            && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only approved school users can create classes
        return $user->user_type === 'school' && $user->school && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SchoolClass $schoolClass): bool
    {
        // Can only update classes belonging to their school
        return $user->user_type === 'school' 
            && $user->school_id === $schoolClass->school_id
            && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SchoolClass $schoolClass): bool
    {
        // Can only delete classes belonging to their school
        return $user->user_type === 'school' 
            && $user->school_id === $schoolClass->school_id
            && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SchoolClass $schoolClass): bool
    {
        // Can only restore classes belonging to their school
        return $user->user_type === 'school' 
            && $user->school_id === $schoolClass->school_id
            && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SchoolClass $schoolClass): bool
    {
        // Can only force delete classes belonging to their school
        return $user->user_type === 'school' 
            && $user->school_id === $schoolClass->school_id
            && $user->school->status === 'approved';
    }
}
