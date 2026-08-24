@extends('layouts.admin')

@section('title', 'EBIMS User Manual')

@php
    $screenshots = [
        'dashboard' => 'userguidelineimages/maindashboard.png',
        'sidebar_search' => 'userguidelineimages/sidebarsearchbar.png',
        'quick_actions' => 'userguidelineimages/quickaction-notification.png',
        'modules' => 'userguidelineimages/ebim module.png',
        'clients' => 'userguidelineimages/client module.png',
        'loan_portfolio' => 'userguidelineimages/loan potifolia.png',
        'active_loans' => 'userguidelineimages/activeloans.png',
        'self_application' => null,
        'collections' => 'userguidelineimages/payments.png',
        'school_management' => 'userguidelineimages/schoolmanagment.png',
        'reports' => 'userguidelineimages/reports.png',
        'investments' => 'userguidelineimages/inestment module.png',
        'access_control' => null,
        'settings' => 'userguidelineimages/systemdashboard.png',
    ];

    $selfApplicationUrl = 'https://ebmis.emuria.net/apply';

    $manualSections = [
        ['id' => 'getting-started', 'label' => 'Getting Started'],
        ['id' => 'sidebar-search', 'label' => 'Find Menu Items'],
        ['id' => 'school-management', 'label' => 'School Management'],
        ['id' => 'clients', 'label' => 'Clients'],
        ['id' => 'loan-portfolio', 'label' => 'Loan Portfolio'],
        ['id' => 'active-loans', 'label' => 'Active Loans'],
        ['id' => 'collections', 'label' => 'Payments & Collections'],
        ['id' => 'reports', 'label' => 'Reports & Accounting'],
        ['id' => 'access-control', 'label' => 'Access Control'],
        ['id' => 'screenshots', 'label' => 'Screenshot Library'],
    ];

    $screenshotBlock = function (string $key, string $caption) use ($screenshots): string {
        $path = $screenshots[$key] ?? null;

        if ($path) {
            return '<figure class="manual-shot"><img src="' . e(asset($path)) . '" alt="' . e($caption) . '"><figcaption>' . e($caption) . '</figcaption></figure>';
        }

        return '<div class="manual-shot manual-shot-empty"><i class="mdi mdi-image-plus"></i><span>' . e($caption) . '</span><small>Screenshot will be added here</small></div>';
    };
@endphp

@section('content')
<div class="ebims-manual-page">
    <div class="manual-hero">
        <div>
            <span class="manual-kicker">EBIMS Help Center</span>
            <h3>EBIMS User Manual</h3>
            <p>Use this manual to learn the main dashboard, sidebar search, school management, client work, loan workflows, collections, reports, and access control.</p>
        </div>
        <div class="manual-hero-actions">
            <a href="{{ auth()->user()?->user_type === 'school' ? route('school.dashboard') : route('admin.home') }}" class="btn btn-outline-secondary btn-sm">
                <i class="mdi mdi-view-dashboard"></i> Dashboard
            </a>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                <i class="mdi mdi-printer"></i> Print Manual
            </button>
            <a href="{{ route('admin.help.guide.download') }}" class="btn btn-success btn-sm">
                <i class="mdi mdi-download"></i> Download PDF
            </a>
        </div>
    </div>

    <div class="manual-layout">
        <aside class="manual-toc">
            <h5>Contents</h5>
            @foreach($manualSections as $section)
                <a href="#{{ $section['id'] }}">{{ $section['label'] }}</a>
            @endforeach
        </aside>

        <main class="manual-content">
            <section class="manual-section" id="getting-started">
                <div class="manual-section-head">
                    <span class="manual-number">01</span>
                    <div>
                        <h4>Getting Started</h4>
                        <p>Begin from the main dashboard, then use the sidebar or EBIMS Modules page to open the work area you need.</p>
                    </div>
                </div>

                <div class="manual-grid two">
                    <div class="manual-card">
                        <h5><i class="mdi mdi-view-dashboard"></i> Main Dashboard</h5>
                        <ol>
                            <li>Review total members, active loans, overdue loans, and payments due today.</li>
                            <li>Use dashboard panels to monitor members, investments, cash securities, and loan values.</li>
                            <li>Open the Help card when you need the user manual.</li>
                            <li>Use Quick Actions for fast links to Active Loans, Self Applications, UMRA Reports, Ledgers, and Expenditures.</li>
                        </ol>
                    </div>
                    {!! $screenshotBlock('dashboard', 'Main dashboard overview') !!}
                </div>

                <div class="manual-grid two manual-grid-spaced">
                    {!! $screenshotBlock('quick_actions', 'Quick actions and notification shortcuts') !!}
                    <div class="manual-card">
                        <h5><i class="mdi mdi-lightning-bolt"></i> Quick actions</h5>
                        <ol>
                            <li>Click Quick Actions in the top bar to open common work queues immediately.</li>
                            <li>Use Active Loans for loan responsibility and repayment follow-up.</li>
                            <li>Use the notification icons to check messages and alerts that need attention.</li>
                        </ol>
                    </div>
                </div>
            </section>

            <section class="manual-section" id="sidebar-search">
                <div class="manual-section-head">
                    <span class="manual-number">02</span>
                    <div>
                        <h4>Find Menu Items</h4>
                        <p>The sidebar search helps users quickly reach pages without knowing which module contains them.</p>
                    </div>
                </div>

                <div class="manual-grid two">
                    {!! $screenshotBlock('sidebar_search', 'Sidebar search: Find menu item') !!}
                    <div class="manual-card">
                        <h5><i class="mdi mdi-magnify"></i> How to search</h5>
                        <ol>
                            <li>Click the Find menu item field in the sidebar.</li>
                            <li>Type a keyword such as members, loans, repayments, reports, roles, settings, or SMS.</li>
                            <li>Matching menu sections will remain visible and dropdowns will open automatically.</li>
                            <li>Press Enter to open the first matching link, or click the link you want.</li>
                            <li>Use the clear icon to restore the normal sidebar menu.</li>
                        </ol>
                    </div>
                </div>
            </section>

            <section class="manual-section" id="school-management">
                <div class="manual-section-head">
                    <span class="manual-number">03</span>
                    <div>
                        <h4>School Management</h4>
                        <p>Use School Management to administer school records and school, student, or staff loan workflows from one place.</p>
                    </div>
                </div>

                <div class="manual-grid two">
                    <div class="manual-card">
                        <h5><i class="mdi mdi-school"></i> Common school tasks</h5>
                        <ol>
                            <li>Open School Management from the sidebar.</li>
                            <li>Use Schools Overview to review registered schools and approval status.</li>
                            <li>Use Add School to register a new school.</li>
                            <li>Open school loan, student loan, or staff loan shortcuts for approvals, disbursements, repayments, and portfolio review.</li>
                            <li>Use Pending Approvals and Active Loans to follow up work that needs attention.</li>
                        </ol>
                    </div>
                    {!! $screenshotBlock('school_management', 'School management module') !!}
                </div>
            </section>

            <section class="manual-section" id="clients">
                <div class="manual-section-head">
                    <span class="manual-number">04</span>
                    <div>
                        <h4>Clients</h4>
                        <p>Use the Clients Module for member registration, approvals, groups, member details, and SMS records.</p>
                    </div>
                </div>

                <div class="manual-grid two">
                    <div class="manual-card">
                        <h5><i class="mdi mdi-account-multiple"></i> Common client tasks</h5>
                        <ol>
                            <li>Open Clients Module from the sidebar or All EBIMS Modules.</li>
                            <li>Use Member Management to search, view, edit, or add members.</li>
                            <li>Use Pending Members to review clients waiting for approval.</li>
                            <li>Open Groups to manage group records and group membership.</li>
                            <li>Use SMS Records or Send Bulk SMS for client communication.</li>
                        </ol>
                    </div>
                    {!! $screenshotBlock('clients', 'Clients module shortcuts') !!}
                </div>
            </section>

            <section class="manual-section" id="loan-portfolio">
                <div class="manual-section-head">
                    <span class="manual-number">05</span>
                    <div>
                        <h4>Loan Portfolio</h4>
                        <p>Use Loan Portfolio for loan creation, self applications, approvals, disbursements, active loan work, and portfolio analysis.</p>
                    </div>
                </div>

                <div class="manual-grid two">
                    {!! $screenshotBlock('loan_portfolio', 'Loan Portfolio module') !!}
                    <div class="manual-card">
                        <h5><i class="mdi mdi-briefcase"></i> Loan workflow</h5>
                        <ol>
                            <li>Create loans from the Create Loans card for personal or group loan products.</li>
                            <li>Use Self Application Form to open the public application page and share the link with clients.</li>
                            <li>Use Self-Applied Applications to review submitted forms.</li>
                            <li>Approve eligible loans, then move them through disbursement.</li>
                            <li>Monitor Active Loan Work for collections, risk follow-up, and security gaps.</li>
                        </ol>
                    </div>
                </div>

                <div class="manual-callout">
                    <i class="mdi mdi-link-variant"></i>
                    <div>
                        <strong>Self application link</strong>
                        <p>The client-facing form is available online at <a href="{{ $selfApplicationUrl }}" target="_blank" rel="noopener">{{ $selfApplicationUrl }}</a>. Staff can open it from Loan Portfolio and share it with clients.</p>
                    </div>
                </div>
            </section>

            <section class="manual-section" id="active-loans">
                <div class="manual-section-head">
                    <span class="manual-number">06</span>
                    <div>
                        <h4>Active Loans</h4>
                        <p>Active Loans is the critical repayment workspace for assigned loans, collections, risk follow-up, collateral checks, and operational loan actions.</p>
                    </div>
                </div>

                <div class="manual-grid two">
                    {!! $screenshotBlock('active_loans', 'Active Loans: schedules, filters, and repayment work') !!}
                    <div class="manual-card">
                        <h5><i class="mdi mdi-bank"></i> What happens on Active Loans</h5>
                        <ol>
                            <li>The page lists running loans with unpaid schedules and shows principal due, interest due, late fees due, overdue loans, and today's collections.</li>
                            <li>Use the tabs to move between Active Personal Loans, Collections Queue, Risk Follow-up, Security Gaps, and Loan Operations where allowed.</li>
                            <li>Use Search, Branch, Product, and Status filters to narrow the visible loans before opening a schedule or exporting records.</li>
                            <li>Open a loan schedule to review installments, pending mobile money requests, previous payments, and the next amount due.</li>
                            <li>Record follow-up notes and collateral details from this workspace so collection history stays attached to the loan.</li>
                        </ol>
                    </div>
                </div>

                <div class="manual-callout manual-callout-warning">
                    <i class="mdi mdi-shield-lock-outline"></i>
                    <div>
                        <strong>Important access rule</strong>
                        <p>Loan Officers, Field Officers, Branch Managers, and other non-administrator roles only see active loans assigned to their user account. Super Administrators and Administrators can view and manage active loans across branches for supervision, reassignment, and operations. This prevents staff from opening, collecting, polling payments, viewing receipts, or exporting active loans that belong to another user.</p>
                    </div>
                </div>
            </section>

            <section class="manual-section" id="collections">
                <div class="manual-section-head">
                    <span class="manual-number">07</span>
                    <div>
                        <h4>Payments & Collections</h4>
                        <p>This area supports repayment follow-up, cash securities, savings, fees, late fees, and mobile money tracking.</p>
                    </div>
                </div>

                <div class="manual-grid two">
                    <div class="manual-card">
                        <h5><i class="mdi mdi-wallet"></i> Collection workflow</h5>
                        <ol>
                            <li>Open Payments & Collections.</li>
                            <li>Use Active Collections or Repayment History to review payments.</li>
                            <li>Open loan schedules to check expected and pending repayment amounts.</li>
                            <li>Use Cash Securities for collateral deposits and returns.</li>
                            <li>Use reports when you need exported repayment or payment transaction records.</li>
                        </ol>
                    </div>
                    {!! $screenshotBlock('collections', 'Payments and collections module') !!}
                </div>
            </section>

            <section class="manual-section" id="reports">
                <div class="manual-section-head">
                    <span class="manual-number">08</span>
                    <div>
                        <h4>Reports & Accounting</h4>
                        <p>Use reports for operational review, accounting checks, regulatory records, exports, and management follow-up.</p>
                    </div>
                </div>

                <div class="manual-grid two">
                    {!! $screenshotBlock('reports', 'Reports and accounting module') !!}
                    <div class="manual-card">
                        <h5><i class="mdi mdi-file-chart-outline"></i> Report workflow</h5>
                        <ol>
                            <li>Open Reports & Accounting.</li>
                            <li>Select the required report, such as loan repayments, paid loans, rejected loans, loan interest, or payment transactions.</li>
                            <li>Use filters before exporting to keep reports focused.</li>
                            <li>Use Accounting pages for journal entries, chart of accounts, trial balance, balance sheet, and income statement.</li>
                            <li>Use UMRA reports for regulatory reporting workflows.</li>
                        </ol>
                    </div>
                </div>
            </section>

            <section class="manual-section" id="access-control">
                <div class="manual-section-head">
                    <span class="manual-number">09</span>
                    <div>
                        <h4>Access Control</h4>
                        <p>Administrators use Access Control to manage users, roles, permissions, and operational access.</p>
                    </div>
                </div>

                <div class="manual-grid two">
                    <div class="manual-card">
                        <h5><i class="mdi mdi-shield-account"></i> Access workflow</h5>
                        <ol>
                            <li>Open Access Control Dashboard.</li>
                            <li>Use User Management to add, review, or update users.</li>
                            <li>Use Roles & Permissions to control what users can view or change.</li>
                            <li>Give users only the permissions required for their work.</li>
                            <li>After changing access, test the sidebar and module pages using that user role.</li>
                        </ol>
                    </div>
                    {!! $screenshotBlock('access_control', 'Access control dashboard') !!}
                </div>
            </section>

            <section class="manual-section" id="screenshots">
                <div class="manual-section-head">
                    <span class="manual-number">10</span>
                    <div>
                        <h4>Screenshot Library</h4>
                        <p>These screenshots support the main workflows in this guide. Remaining placeholders can be filled as more images are added.</p>
                    </div>
                </div>

                <div class="manual-shot-grid">
                    @foreach([
                        'dashboard' => 'Main dashboard',
                        'sidebar_search' => 'Sidebar search',
                        'quick_actions' => 'Quick actions and notifications',
                        'modules' => 'All EBIMS Modules',
                        'school_management' => 'School Management',
                        'clients' => 'Clients Module',
                        'loan_portfolio' => 'Loan Portfolio',
                        'active_loans' => 'Active Loans',
                        'self_application' => 'Self Application Form',
                        'collections' => 'Payments & Collections',
                        'reports' => 'Reports & Accounting',
                        'investments' => 'Investments Module',
                        'access_control' => 'Access Control',
                        'settings' => 'Settings Dashboard',
                    ] as $key => $caption)
                        {!! $screenshotBlock($key, $caption) !!}
                    @endforeach
                </div>
            </section>
        </main>
    </div>
</div>
@endsection

@push('styles')
<style>
.ebims-manual-page {
    color: #172033;
}

.manual-hero {
    align-items: center;
    background: #ffffff;
    border: 1px solid #dfe7ef;
    border-left: 4px solid #2563eb;
    border-radius: 8px;
    display: flex;
    gap: 18px;
    justify-content: space-between;
    margin-bottom: 20px;
    padding: 20px;
}

.manual-kicker {
    color: #2563eb;
    display: block;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.08em;
    margin-bottom: 6px;
    text-transform: uppercase;
}

.manual-hero h3,
.manual-section h4,
.manual-card h5 {
    color: #111827;
    font-weight: 800;
}

.manual-hero p,
.manual-section-head p,
.manual-card p,
.manual-card li,
.manual-callout p {
    color: #64748b;
}

.manual-hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.manual-layout {
    align-items: flex-start;
    display: grid;
    gap: 20px;
    grid-template-columns: 240px minmax(0, 1fr);
}

.manual-toc {
    background: #ffffff;
    border: 1px solid #dfe7ef;
    border-radius: 8px;
    padding: 16px;
    position: sticky;
    top: 92px;
}

.manual-toc h5 {
    font-size: 14px;
    font-weight: 800;
    margin-bottom: 10px;
}

.manual-toc a {
    border-radius: 6px;
    color: #475569;
    display: block;
    font-size: 13px;
    font-weight: 700;
    padding: 8px 10px;
    text-decoration: none;
}

.manual-toc a:hover {
    background: #eff6ff;
    color: #2563eb;
}

.manual-section {
    background: #ffffff;
    border: 1px solid #dfe7ef;
    border-radius: 8px;
    margin-bottom: 18px;
    padding: 20px;
}

.manual-section-head {
    align-items: flex-start;
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
}

.manual-number {
    align-items: center;
    background: #111827;
    border-radius: 8px;
    color: #ffffff;
    display: inline-flex;
    flex: 0 0 42px;
    font-size: 13px;
    font-weight: 800;
    height: 42px;
    justify-content: center;
}

.manual-grid {
    display: grid;
    gap: 16px;
}

.manual-grid.two {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.manual-grid-spaced {
    margin-top: 16px;
}

.manual-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 18px;
}

.manual-card h5 i {
    color: #2563eb;
    margin-right: 6px;
}

.manual-card ol {
    margin: 12px 0 0;
    padding-left: 19px;
}

.manual-card li {
    margin-bottom: 8px;
}

.manual-shot {
    align-items: center;
    background: #f8fafc;
    border: 1px dashed #94a3b8;
    border-radius: 8px;
    color: #64748b;
    display: flex;
    flex-direction: column;
    font-size: 13px;
    font-weight: 700;
    gap: 4px;
    justify-content: center;
    min-height: 220px;
    overflow: hidden;
    padding: 16px;
    text-align: center;
}

.manual-shot img {
    display: block;
    height: auto;
    width: 100%;
}

.manual-shot figcaption {
    color: #475569;
    font-size: 12px;
    margin-top: 8px;
}

.manual-shot-empty i {
    color: #2563eb;
    font-size: 32px;
}

.manual-shot-empty small {
    color: #94a3b8;
    font-size: 12px;
    font-weight: 600;
}

.manual-callout {
    align-items: flex-start;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    display: flex;
    gap: 12px;
    margin-top: 16px;
    padding: 14px;
}

.manual-callout-warning {
    background: #fff7ed;
    border-color: #fed7aa;
}

.manual-callout > i {
    color: #2563eb;
    font-size: 24px;
}

.manual-callout-warning > i {
    color: #ea580c;
}

.manual-callout p {
    margin-bottom: 0;
}

.manual-shot-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

@media (max-width: 1100px) {
    .manual-layout {
        grid-template-columns: 1fr;
    }

    .manual-toc {
        position: static;
    }

    .manual-shot-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767px) {
    .manual-hero,
    .manual-section-head {
        flex-direction: column;
    }

    .manual-grid.two,
    .manual-shot-grid {
        grid-template-columns: 1fr;
    }
}

@media print {
    .sidebar,
    .navbar,
    .page-back-nav,
    .manual-toc,
    .manual-hero-actions,
    .footer {
        display: none !important;
    }

    .main-panel,
    .content-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .manual-section {
        break-inside: avoid;
    }
}
</style>
@endpush
