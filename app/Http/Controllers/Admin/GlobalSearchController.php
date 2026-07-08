<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoanAccessService;
use App\Support\EbmisPermissionRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class GlobalSearchController extends Controller
{
    public function __construct(private LoanAccessService $loanAccessService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([
                'query' => $term,
                'results' => [],
            ]);
        }

        $user = $request->user();

        $results = collect($this->componentCatalogue())
            ->filter(fn (array $component): bool => $this->canAccessComponent($component, $user))
            ->map(function (array $component) use ($term): ?array {
                $score = $this->matchScore($component, $term);

                if ($score === null) {
                    return null;
                }

                return [
                    'type' => $component['group'],
                    'icon' => $component['icon'] ?? 'mdi-apps',
                    'title' => $component['title'],
                    'subtitle' => $component['subtitle'],
                    'url' => route($component['route'], $component['params'] ?? []),
                    '_score' => $score,
                ];
            })
            ->filter()
            ->sortBy([
                ['_score', 'asc'],
                ['type', 'asc'],
                ['title', 'asc'],
            ])
            ->take(12)
            ->values()
            ->map(function (array $result): array {
                unset($result['_score']);
                return $result;
            });

        return response()->json([
            'query' => $term,
            'results' => $results,
        ]);
    }

    private function componentCatalogue(): array
    {
        return [
            $this->component('Dashboard', 'Operational dashboard and daily overview.', 'Workspace', 'admin.home', 'mdi-view-dashboard', ['home', 'dashboard', 'overview']),

            $this->component('Members List', 'Search, view, and manage client profiles.', 'Clients', 'admin.members.index', 'mdi-account-multiple', ['clients', 'customers', 'borrowers', 'member list']),
            $this->component('Pending Members', 'Review members waiting for approval.', 'Clients', 'admin.members.pending', 'mdi-account-clock', ['pending clients', 'pending members', 'approvals']),
            $this->component('Groups', 'Manage client groups and group membership.', 'Clients', 'admin.groups.index', 'mdi-account-group', ['group clients', 'group list', 'groups']),

            $this->component('Active Loans', 'Schedules and payments for active personal loans.', 'Loans', 'admin.loans.active', 'mdi-bank', ['active loans', 'loan schedules', 'repayments'], ['type' => 'personal']),
            $this->component('Active Group Loans', 'Schedules and follow-up for group loans.', 'Loans', 'admin.loans.active', 'mdi-account-group', ['group loans', 'active group loans'], ['type' => 'group']),
            $this->component('Collections Queue', 'Loans that need collection action.', 'Loans', 'admin.loans.active.collections', 'mdi-cash-clock', ['collections', 'collection queue', 'due loans']),
            $this->component('Risk Follow-up', 'Overdue and high-risk active loans.', 'Loans', 'admin.loans.active.risk-follow-up', 'mdi-alert-circle-outline', ['risk', 'follow up', 'overdue']),
            $this->component('Security Gaps', 'Active loans missing collateral/security records.', 'Loans', 'admin.loans.active.security-gaps', 'mdi-shield-alert', ['collateral', 'security gaps', 'missing security']),
            $this->component('Loan Operations', 'Restructure, stop, revert, and reassignment operations.', 'Loans', 'admin.loans.active.operations', 'mdi-tools', ['operations', 'restructure', 'stop loan', 'reassign']),
            $this->component('Loan Approvals', 'Review loan applications awaiting decisions.', 'Loans', 'admin.loans.approvals', 'mdi-clipboard-check-outline', ['approvals', 'loan approvals', 'pending loans']),
            $this->component('Rejected Loans', 'Rejected personal loan applications.', 'Loans', 'admin.loans.rejected', 'mdi-close-circle-outline', ['rejected loans', 'declined loans']),
            $this->component('Client Applications', 'Self-service loan applications awaiting review.', 'Loans', 'admin.client-applications.index', 'mdi-file-account', ['self applications', 'client applications', 'fo verification']),
            $this->component('Disbursements', 'Pending and completed loan disbursements.', 'Loans', 'admin.disbursements.index', 'mdi-bank-transfer-out', ['disbursement', 'disbursements']),
            $this->component('Repayment History', 'Repayment records and receipts.', 'Collections', 'admin.repayments.index', 'mdi-receipt', ['repayments', 'receipts', 'payment history']),
            $this->component('Fees', 'Member and loan fee collections.', 'Collections', 'admin.fees.index', 'mdi-cash-multiple', ['fees', 'charges', 'collections']),
            $this->component('Late Fees', 'Late fee records and monitoring.', 'Collections', 'admin.late-fees.index', 'mdi-timer-alert-outline', ['late fees', 'penalties']),
            $this->component('Portfolio Running', 'Running loan portfolio report.', 'Reports', 'admin.portfolio.running', 'mdi-chart-line', ['portfolio', 'running loans']),
            $this->component('Pending Loan Report', 'Pending loan applications report.', 'Reports', 'admin.reports.pending-loans', 'mdi-file-clock-outline', ['reports', 'pending loans']),
            $this->component('Disbursed Loans Report', 'Disbursed loans report.', 'Reports', 'admin.reports.disbursed-loans', 'mdi-file-chart-outline', ['reports', 'disbursed loans']),
            $this->component('Rejected Loans Report', 'Rejected loans report.', 'Reports', 'admin.reports.rejected-loans', 'mdi-file-remove-outline', ['reports', 'rejected loans']),
            $this->component('Loans Due Report', 'Loans due and expected repayment report.', 'Reports', 'admin.reports.loans-due', 'mdi-calendar-alert', ['reports', 'loans due']),
            $this->component('Paid Loans Report', 'Paid and closed loans report.', 'Reports', 'admin.reports.paid-loans', 'mdi-file-check-outline', ['reports', 'paid loans']),
            $this->component('Loan Repayments Report', 'Repayment collections report.', 'Reports', 'admin.reports.loan-repayments', 'mdi-file-document-outline', ['reports', 'loan repayments']),
            $this->component('Payment Transactions', 'Payment transaction report.', 'Reports', 'admin.reports.payment-transactions', 'mdi-credit-card-search-outline', ['reports', 'transactions', 'payment transactions']),
            $this->component('Loan Interest Report', 'Loan interest report.', 'Reports', 'admin.reports.loan-interest', 'mdi-percent-outline', ['reports', 'interest']),
            $this->component('Cash Securities Report', 'Cash securities report.', 'Reports', 'admin.reports.cash-securities', 'mdi-shield-search', ['reports', 'cash securities']),
            $this->component('Loan Charges Report', 'Loan charges report.', 'Reports', 'admin.reports.loan-charges', 'mdi-file-percent-outline', ['reports', 'loan charges']),

            $this->component('Journal Entries', 'General ledger journal entries.', 'Ledgers', 'admin.accounting.journal-entries', 'mdi-book-open-page-variant', ['ledger', 'ledgers', 'journal', 'general ledger']),
            $this->component('Chart of Accounts', 'Accounting chart of accounts.', 'Ledgers', 'admin.accounting.chart-of-accounts', 'mdi-format-list-bulleted-type', ['ledger', 'chart accounts', 'accounts']),
            $this->component('Trial Balance', 'Trial balance report.', 'Ledgers', 'admin.accounting.trial-balance', 'mdi-scale-balance', ['ledger', 'trial balance']),
            $this->component('Balance Sheet', 'Balance sheet report.', 'Ledgers', 'admin.accounting.balance-sheet', 'mdi-file-table-outline', ['ledger', 'balance sheet']),
            $this->component('Income Statement', 'Income statement and profit/loss report.', 'Ledgers', 'admin.accounting.income-statement', 'mdi-chart-areaspline', ['ledger', 'income statement', 'profit loss', 'p&l']),

            $this->component('UMRA Portfolio Indicators', 'UMRA regulatory portfolio dashboard.', 'UMRA', 'admin.umra.dashboard', 'mdi-chart-box-outline', ['umra', 'portfolio indicators']),
            $this->component('UMRA Loan Preview', 'UMRA loan preview records.', 'UMRA', 'admin.umra.loan-preview', 'mdi-file-eye-outline', ['umra', 'loan preview']),
            $this->component('UMRA Loan Records', 'UMRA loan records report.', 'UMRA', 'admin.umra.loan-records', 'mdi-file-document-multiple-outline', ['umra', 'loan records']),
            $this->component('UMRA Collateral Register', 'Regulatory collateral register.', 'UMRA', 'admin.umra.collateral-register', 'mdi-shield-file-outline', ['umra', 'collateral register']),
            $this->component('UMRA Schedule 3', 'Risk classification schedule 3.', 'UMRA', 'admin.umra.schedule3', 'mdi-file-alert-outline', ['umra', 'schedule 3', 'risk classification']),
            $this->component('UMRA Prudential Pack', 'Prudential reporting pack.', 'UMRA', 'admin.umra.prudential-pack', 'mdi-package-variant-closed', ['umra', 'prudential pack']),

            $this->component('Savings Accounts', 'Savings account management.', 'Savings', 'admin.savings.index', 'mdi-piggy-bank-outline', ['savings', 'savings accounts']),
            $this->component('Investments', 'Investment dashboard and records.', 'Investments', 'admin.investments.index', 'mdi-finance', ['investments', 'investors']),
            $this->component('Expenditures', 'Expenditure records and payments.', 'Accounting', 'admin.expenditures.index', 'mdi-cash-minus', ['expenditures', 'expenses']),
            $this->component('Staff Payment Rollout', 'Weekly staff performance payment rollout.', 'Accounting', 'admin.expenditures.rollout', 'mdi-account-cash-outline', ['staff payment', 'salary rollout', 'pay officers', 'performance payout', 'expenses']),

            $this->component('Access Control', 'Users, roles, and permission administration.', 'Settings', 'admin.access-control.index', 'mdi-account-key-outline', ['access control', 'roles', 'permissions', 'users'], [], true),
            $this->component('User Management', 'Manage system users.', 'Settings', 'admin.users.index', 'mdi-account-cog-outline', ['users', 'staff users'], [], true),
            $this->component('Roles', 'Manage system roles.', 'Settings', 'admin.roles.index', 'mdi-shield-account-outline', ['roles', 'role management'], [], true),
            $this->component('Permissions', 'Manage system permissions.', 'Settings', 'admin.permissions.index', 'mdi-key-chain', ['permissions', 'permission management'], [], true),
            $this->component('Settings Dashboard', 'System settings dashboard.', 'Settings', 'admin.settings.dashboard', 'mdi-cog-outline', ['settings', 'system settings'], [], true),
            $this->component('Agency Management', 'Manage agencies.', 'Settings', 'admin.settings.agencies', 'mdi-domain', ['settings', 'agencies'], [], true),
            $this->component('Branch Management', 'Manage branches.', 'Settings', 'admin.settings.branches', 'mdi-source-branch', ['settings', 'branches'], [], true),
            $this->component('Field Users', 'Manage field users.', 'Settings', 'admin.settings.field-users', 'mdi-account-hard-hat', ['settings', 'field users'], [], true),
            $this->component('Company Information', 'Company profile settings.', 'Settings', 'admin.settings.company-info', 'mdi-office-building-cog-outline', ['settings', 'company information'], [], true),
            $this->component('Loan Products', 'Loan product settings.', 'Settings', 'admin.settings.loan-products', 'mdi-bank-plus', ['settings', 'loan products', 'products'], [], true),
            $this->component('School Loan Products', 'School loan product settings.', 'Settings', 'admin.settings.school-loan-products', 'mdi-school-outline', ['settings', 'school loan products'], [], true),
            $this->component('Savings Products', 'Savings product settings.', 'Settings', 'admin.settings.savings-products', 'mdi-piggy-bank', ['settings', 'savings products'], [], true),
            $this->component('Fees & Products', 'Fee product settings.', 'Settings', 'admin.settings.fees-products', 'mdi-cash-cog', ['settings', 'fees products'], [], true),
            $this->component('System Accounts', 'System and chart account settings.', 'Settings', 'admin.settings.system-accounts', 'mdi-book-cog-outline', ['settings', 'system accounts', 'chart accounts'], [], true),
            $this->component('Account Types', 'Account type settings.', 'Settings', 'admin.settings.account-types', 'mdi-format-list-group', ['settings', 'account types'], [], true),
            $this->component('Security Codes', 'Security code settings.', 'Settings', 'admin.settings.security-codes', 'mdi-lock-check-outline', ['settings', 'security codes'], [], true),
            $this->component('Transaction Codes', 'Transaction code settings.', 'Settings', 'admin.settings.transaction-codes', 'mdi-barcode-scan', ['settings', 'transaction codes'], [], true),
            $this->component('Audit Trail Settings', 'Audit trail configuration.', 'Settings', 'admin.settings.audit-trail', 'mdi-clipboard-text-clock-outline', ['settings', 'audit trail'], [], true),
            $this->component('Loan Policy Controls', 'Loan scoring and policy controls.', 'Settings', 'admin.settings.loan-policy-controls', 'mdi-tune-variant', ['settings', 'loan policy', 'scoring'], [], true),
        ];
    }

    private function component(
        string $title,
        string $subtitle,
        string $group,
        string $route,
        string $icon,
        array $keywords = [],
        array $params = [],
        bool $superAdminOnly = false
    ): array {
        return compact('title', 'subtitle', 'group', 'route', 'icon', 'keywords', 'params', 'superAdminOnly');
    }

    private function canAccessComponent(array $component, ?User $user): bool
    {
        if (!$user || !Route::has($component['route'])) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (($component['superAdminOnly'] ?? false) === true) {
            return false;
        }

        if (in_array($component['route'], config('ebmis_permissions.sensitive_staff_payment_rollout_routes', []), true)) {
            return $user->canManageStaffPaymentRollout();
        }

        if (in_array($component['route'], config('ebmis_permissions.sensitive_super_admin_routes', []), true)) {
            return false;
        }

        if (in_array($component['route'], config('ebmis_permissions.sensitive_loan_operations_admin_routes', []), true)) {
            return $this->loanAccessService->canManageSensitiveLoanOperations($user);
        }

        $permission = EbmisPermissionRegistry::routePermission($component['route']);

        return $permission !== null && $user->can($permission);
    }

    private function matchScore(array $component, string $term): ?int
    {
        $needle = Str::lower($term);
        $title = Str::lower($component['title']);
        $group = Str::lower($component['group']);
        $subtitle = Str::lower($component['subtitle']);
        $keywords = Str::lower(implode(' ', $component['keywords'] ?? []));
        $haystack = trim($title . ' ' . $group . ' ' . $subtitle . ' ' . $keywords);

        if (!Str::contains($haystack, $needle)) {
            return null;
        }

        if ($title === $needle) {
            return 0;
        }

        if (Str::startsWith($title, $needle)) {
            return 1;
        }

        if (Str::contains($title, $needle)) {
            return 2;
        }

        if (Str::contains($group, $needle)) {
            return 3;
        }

        return 4;
    }
}
