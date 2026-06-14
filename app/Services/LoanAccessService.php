<?php

namespace App\Services;

use App\Models\User;

class LoanAccessService
{
    public function canMakeLoanDecision(?User $user = null, string $permission = 'approve-loan'): bool
    {
        $user ??= auth()->user();

        return (bool) (
            $user?->isSuperAdmin() ||
            $user?->can($permission)
        );
    }

    public function scopeBranchQuery($query, string $branchColumn = 'branch_id', ?User $user = null)
    {
        $user ??= auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if (!$user->branch_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($branchColumn, $user->branch_id);
    }

    public function scopeLoanQuery(
        $query,
        string $branchColumn = 'branch_id',
        string $assignedColumn = 'assigned_to',
        string $addedByColumn = 'added_by',
        ?User $user = null
    ) {
        $user ??= auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        return $this->scopeBranchQuery($query, $branchColumn, $user);
    }

    public function canWorkAcrossBranchesOnActiveLoans(?User $user = null): bool
    {
        $user ??= auth()->user();

        return (bool) (
            $user?->isSuperAdmin() ||
            $user?->can('view-active-loans')
        );
    }

    public function canReassignActiveLoans(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (!$user) {
            return false;
        }

        return $user->isSuperAdmin()
            || in_array($user->user_type, ['administrator', 'admin'], true)
            || $user->hasRole(['Administrator', 'admin']);
    }

    public function assignableLoanUsers()
    {
        return User::query()
            ->where('status', 'active')
            ->where('user_type', '!=', 'school')
            ->where(function ($query) {
                $query->whereHas('roles', function ($roleQuery) {
                    $roleQuery->whereIn('name', ['Loan Officer', 'Field Officer', 'Branch Manager']);
                })
                    ->orWhere('user_type', 'branch')
                    ->orWhere(function ($designationQuery) {
                        $designationQuery->where('designation', 'like', '%officer%')
                            ->orWhere('designation', 'like', '%manager%');
                    });
            })
            ->with('branch:id,name')
            ->orderBy('name')
            ->get();
    }

    public function scopeActiveLoanQuery($query, string $branchColumn = 'branch_id', ?User $user = null)
    {
        $user ??= auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->canWorkAcrossBranchesOnActiveLoans($user)) {
            return $query;
        }

        return $this->scopeBranchQuery($query, $branchColumn, $user);
    }

    public function ensureBranchAccess($record, string $branchKey = 'branch_id', ?User $user = null): void
    {
        $user ??= auth()->user();

        if (!$user) {
            abort(403, 'Access denied.');
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        if (!$user->branch_id || (int) $record->{$branchKey} !== (int) $user->branch_id) {
            abort(403, 'Access denied. This record belongs to another branch.');
        }
    }

    public function ensureLoanAccess($loan, ?User $user = null): void
    {
        $user ??= auth()->user();

        if (!$user) {
            abort(403, 'Access denied.');
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        if (
            $this->canWorkAcrossBranchesOnActiveLoans($user) &&
            in_array((string) $loan->status, ['2', '3'], true)
        ) {
            return;
        }

        $this->ensureBranchAccess($loan, 'branch_id', $user);
    }

    public function ensureLoanDecisionAccess($loan, ?User $user = null, string $permission = 'approve-loan'): void
    {
        $user ??= auth()->user();

        $this->ensureLoanAccess($loan, $user);

        if (!$this->canMakeLoanDecision($user, $permission)) {
            abort(403, "Access denied. Your role does not include the {$permission} permission.");
        }
    }

    public function branchesForUser($query, ?User $user = null)
    {
        $user ??= auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        return $this->scopeBranchQuery($query, 'id', $user);
    }

    public function branchesForActiveLoanOperations($query, ?User $user = null)
    {
        $user ??= auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->canWorkAcrossBranchesOnActiveLoans($user)) {
            return $query;
        }

        return $this->scopeBranchQuery($query, 'id', $user);
    }

    public function enforceRequestedBranch($branchId, ?User $user = null): int
    {
        $user ??= auth()->user();

        if (!$user) {
            abort(403, 'Access denied.');
        }

        if ($user->isSuperAdmin()) {
            return (int) $branchId;
        }

        if (!$user->branch_id) {
            abort(403, 'Access denied. Your user account is not assigned to a branch.');
        }

        if ((int) $branchId !== (int) $user->branch_id) {
            abort(403, 'Access denied. You cannot create or move records into another branch.');
        }

        return (int) $user->branch_id;
    }
}
