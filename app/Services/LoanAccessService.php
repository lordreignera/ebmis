<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Schema;

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
            $user?->isAdministrator()
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

    public function canManageSensitiveLoanOperations(?User $user = null): bool
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

    public function scopeActiveLoanQuery(
        $query,
        string $branchColumn = 'branch_id',
        string $assignedColumn = 'assigned_to',
        ?User $user = null
    )
    {
        $user ??= auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->canWorkAcrossBranchesOnActiveLoans($user)) {
            return $query;
        }

        if (!$this->queryHasColumn($query, $assignedColumn)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($assignedColumn, $user->id);
    }

    public function scopeRepaymentQueryByLoanAccess(
        $query,
        string $loanRelation = 'loan',
        ?User $user = null
    ) {
        $user ??= auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->canWorkAcrossBranchesOnActiveLoans($user)) {
            return $query;
        }

        return $query->whereHas($loanRelation, function ($loanQuery) use ($user) {
            $loanQuery->where('assigned_to', $user->id);
        });
    }

    public function scopeRepaymentTableByLoanAccess(
        $query,
        string $loanAlias = 'l',
        ?User $user = null
    ) {
        $user ??= auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->canWorkAcrossBranchesOnActiveLoans($user)) {
            return $query;
        }

        if (!$this->queryHasColumn($query, "{$loanAlias}.assigned_to", $loanAlias)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where("{$loanAlias}.assigned_to", $user->id);
    }

    private function queryHasColumn($query, string $qualifiedColumn, ?string $tableAlias = null): bool
    {
        [$columnAlias, $column] = $this->splitQualifiedColumn($qualifiedColumn);
        $tableAlias ??= $columnAlias;
        $table = $this->resolveQueryTable($query, $tableAlias);

        if (!$table) {
            return true;
        }

        return Schema::hasColumn($table, $column);
    }

    private function splitQualifiedColumn(string $qualifiedColumn): array
    {
        $parts = explode('.', str_replace('`', '', $qualifiedColumn));

        if (count($parts) === 1) {
            return [null, $parts[0]];
        }

        return [$parts[count($parts) - 2], end($parts)];
    }

    private function resolveQueryTable($query, ?string $tableAlias): ?string
    {
        if ($query instanceof EloquentBuilder) {
            $baseQuery = $query->getQuery();
            $modelTable = $query->getModel()->getTable();

            if (!$tableAlias || $tableAlias === $modelTable) {
                return $modelTable;
            }

            return $this->resolveQueryBuilderTable($baseQuery, $tableAlias) ?? $modelTable;
        }

        if ($query instanceof QueryBuilder) {
            return $this->resolveQueryBuilderTable($query, $tableAlias);
        }

        return null;
    }

    private function resolveQueryBuilderTable(QueryBuilder $query, ?string $tableAlias): ?string
    {
        foreach (array_filter([$query->from ?? null]) as $tableExpression) {
            [$table, $alias] = $this->parseTableExpression($tableExpression);

            if (!$tableAlias || $tableAlias === $alias || $tableAlias === $table) {
                return $table;
            }
        }

        foreach ($query->joins ?? [] as $join) {
            [$table, $alias] = $this->parseTableExpression($join->table);

            if ($tableAlias === $alias || $tableAlias === $table) {
                return $table;
            }
        }

        return null;
    }

    private function parseTableExpression($tableExpression): array
    {
        $expression = trim(str_replace('`', '', (string) $tableExpression));

        if (preg_match('/^(.+?)\s+as\s+([A-Za-z_][A-Za-z0-9_]*)$/i', $expression, $matches)) {
            return [$this->normalizeTableName($matches[1]), $matches[2]];
        }

        if (preg_match('/^(.+?)\s+([A-Za-z_][A-Za-z0-9_]*)$/', $expression, $matches)) {
            return [$this->normalizeTableName($matches[1]), $matches[2]];
        }

        $table = $this->normalizeTableName($expression);

        return [$table, $table];
    }

    private function normalizeTableName(string $table): string
    {
        $parts = explode('.', trim($table));

        return end($parts);
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

        $isActiveLoan = in_array((string) $loan->status, ['2', '3'], true);

        if ($this->canWorkAcrossBranchesOnActiveLoans($user) && $isActiveLoan) {
            return;
        }

        if ($isActiveLoan) {
            if ((int) ($loan->assigned_to ?? 0) === (int) $user->id) {
                return;
            }

            abort(403, 'Access denied. This active loan is assigned to another user.');
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
