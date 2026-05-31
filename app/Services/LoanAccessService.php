<?php

namespace App\Services;

use App\Models\User;

class LoanAccessService
{
    public function isFieldOfficer(?User $user = null): bool
    {
        $user ??= auth()->user();

        return (bool) $user?->hasAnyRole(['Loan Officer', 'Field Officer']);
    }

    public function canMakeLoanDecision(?User $user = null): bool
    {
        $user ??= auth()->user();

        return (bool) ($user?->isSuperAdmin() || $user?->hasRole('Branch Manager'));
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
        $query = $this->scopeBranchQuery($query, $branchColumn, $user);

        if (!$user || $user->isSuperAdmin() || !$this->isFieldOfficer($user)) {
            return $query;
        }

        return $query->where(function ($query) use ($assignedColumn, $addedByColumn, $user) {
            $query->where($assignedColumn, $user->id)
                ->orWhere($addedByColumn, $user->id);
        });
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
        $this->ensureBranchAccess($loan, 'branch_id', $user);

        if (!$user || $user->isSuperAdmin() || !$this->isFieldOfficer($user)) {
            return;
        }

        if ((int) ($loan->assigned_to ?? 0) !== (int) $user->id
            && (int) ($loan->added_by ?? 0) !== (int) $user->id) {
            abort(403, 'Access denied. This loan is not assigned to you.');
        }
    }

    public function ensureLoanDecisionAccess($loan, ?User $user = null): void
    {
        $user ??= auth()->user();
        $this->ensureLoanAccess($loan, $user);

        if (!$this->canMakeLoanDecision($user)) {
            abort(403, 'Access denied. Only a Branch Manager or Super Administrator can approve or reject loans.');
        }
    }

    public function branchesForUser($query, ?User $user = null)
    {
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
