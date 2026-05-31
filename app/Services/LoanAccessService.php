<?php

namespace App\Services;

use App\Models\User;

class LoanAccessService
{
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

        // Retain the assignment column parameters for callers using the legacy
        // signature; loan visibility itself is now intentionally branch-based.
        // Officers may collect repayments or record collateral for another
        // officer's loan, while branch separation still remains enforced.
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
        $this->ensureBranchAccess($loan, 'branch_id', $user);
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
