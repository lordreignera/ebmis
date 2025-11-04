<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StaffPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // School users can view their own staff
        return $user->user_type === 'school' && $user->school && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Staff $staff): bool
    {
        // Can only view staff belonging to their school
        return $user->user_type === 'school' 
            && $user->school_id === $staff->school_id
            && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only approved school users can create staff
        return $user->user_type === 'school' && $user->school && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Staff $staff): bool
    {
        // Can only update staff belonging to their school
        return $user->user_type === 'school' 
            && $user->school_id === $staff->school_id
            && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Staff $staff): bool
    {
        // Can only delete staff belonging to their school
        return $user->user_type === 'school' 
            && $user->school_id === $staff->school_id
            && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Staff $staff): bool
    {
        // Can only restore staff belonging to their school
        return $user->user_type === 'school' 
            && $user->school_id === $staff->school_id
            && $user->school->status === 'approved';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Staff $staff): bool
    {
        // Can only force delete staff belonging to their school
        return $user->user_type === 'school' 
            && $user->school_id === $staff->school_id
            && $user->school->status === 'approved';
    }
}
