# EBIMS System Module Review

**Review date:** 27 August 2026

**Full recheck:** 27 August 2026, 20:30 EAT

**Purpose:** Meeting-readiness review of every major system module
**Overall assessment:** The system's main operational backbone is meeting-ready. Fifty-one representative pages, APIs, and downloads were smoke-tested against the current MySQL data and all 51 passed. The loan portfolio now follows the real numeric lifecycle from Pending (0) through Stopped (6); the Restructured view follows a status 5 original to its operational `R...` replacement loan, and the obsolete Individual Portfolio route/module entry was removed. A separate implementation audit still identifies 12 unrelated legacy endpoints that should not be presented as complete.

## Executive summary

### Best modules to demonstrate

1. Authentication, profiles, password security, teams, and access control
2. Admin dashboard and EBIMS module navigation
3. Client/member records and self-service client applications
4. Core loan workflow: applications, approvals, e-signing, active-loan work, schedules, access restrictions, and repayment calculations
5. Disbursement list and approval workflow
6. Main repayment list, late fees, fees, and cash securities
7. Portfolio, operational reports, accounting, and UMRA reporting
8. School registration, school administration, school portal, and school loan creation
9. Branch/agency configuration, users, roles, permissions, and system settings

### Recovered for the meeting

1. **Savings:** main, pending, and approved pages now render against the legacy schema.
2. **Expenditures:** the undefined action-state variables were corrected and the main page renders.
3. **Repayments:** Pending Repayments and Repayment History now use distinct filtered views.
4. **Groups:** Suspend and Activate now update the legacy `verified` state safely.
5. **Investments:** investor editing/status actions, investment cancellation, portfolio API, and statistics API are implemented.
6. **Schools:** Admin Create School creates the school and its first administrator in one transaction.
7. **Active loans:** filtered Excel and PDF export is implemented; the Excel download was smoke-tested.

### Still avoid

Generic loan delete/history, generic disbursement edit/update/delete, bulk-SMS edit/update, fee member search, savings delete, and savings-product create/show/edit remain legacy route gaps. These are outside the repaired meeting path and are listed below.

## Evidence collected

| Check | Result |
|---|---:|
| PHPUnit backend suite | 126 tests, 783 assertions, 0 failures, 8 skipped |
| Targeted meeting recovery suite | 6 tests, 44 assertions, 0 failures |
| Live MySQL module smoke test | 51 passed, 0 failed |
| Registered routes | 504 |
| Route security audit | 421 admin routes checked, 0 issues, 0 warnings |
| Invalid controller-backed routes | 12 (all outside the repaired meeting list) |
| Blade templates | 299 files; cache compilation passed |
| Frontend production build | Passed; 53 modules transformed |
| Database migrations | All migrations applied through `2026_08_24_000003` |
| Timezone | PHP, Laravel, Carbon, and MySQL correctly use EAT/UTC+03:00 |
| Composer manifest | Valid; one nonblocking exact-version warning |
| TODO/placeholder scan | No TODO, FIXME, mock-data, coming-soon, or unimplemented markers found |

The eight skipped tests are Jetstream features that are disabled in the current configuration, mainly API tokens, email verification, and open registration. They are not test failures.

## Module-by-module assessment

Status meanings:

- **Strong:** live entry page passed and important behavior has automated coverage.
- **Working:** live entry page passed; deeper mutation testing is still limited.
- **Working with gaps:** the main workflow opens, but one or more registered actions are broken.
- **Not demo-ready:** a main entry page currently fails.

| Module | Status | Review finding |
|---|---|---|
| Public login and account security | **Strong** | Login, invalid-password rejection, reset, confirmation, profile update, password update, browser-session logout, and two-factor settings pass automated tests. |
| User teams | **Strong** | Team create/update/delete, invitations, membership, roles, and leave/remove rules pass tests. |
| API tokens and email verification | **Configuration-dependent** | Related Jetstream tests are skipped because these features are disabled. |
| Admin dashboard and module workspace | **Working** | Dashboard and module navigation render against live data. |
| Global search and help | **Working** | Routes and controller actions exist; no dedicated functional tests were found. |
| Public client loan application | **Working** | Application page renders; admin application list renders. Verification/approval routes exist, but the complete mutation flow is not covered by the main test suite. |
| Client/member records | **Working** | Index renders; create/edit/show, approvals, duplicate checks, and search actions exist. |
| Member business/assets/liabilities/documents | **Working** | CRUD and document routes map to real controller methods. No broken actions were detected. |
| Groups | **Strong for meeting path** | Main list renders. Suspend and Activate use the legacy numeric verification state and pass regression tests. |
| Bulk SMS | **Working with gaps** | Main campaign list renders. Resource-generated edit and update routes point to missing methods. Create/store/show/delete exist. |
| Loan products and charges | **Working** | Core loan-product and charge controllers have their expected methods. Settings page renders. |
| Client scoring and loan policy controls | **Working** | Services, policy routes, and settings views exist; no dedicated end-to-end scoring test was found. |
| Loan applications and approvals | **Strong** | Lists and approval pages render. Service registration, controller injection, calculations, schedules, route registration, and the main workflow pass automated tests. |
| Loan access and security | **Strong** | Branch manager, field officer, administrator, super-admin, sensitive-operation, and deny-by-default route rules are extensively tested. |
| E-signing and agreements | **Working** | Controller actions and views exist. This review did not execute OTP delivery or signing mutations. |
| Active loans, collateral, follow-up, restructuring | **Strong with small gaps** | Work queues render and access restrictions are tested. Filtered Excel export passes a live download check and regression test. Only unrelated generic loan history/delete routes remain incomplete. |
| Disbursements | **Working with gaps** | Main list renders and loan-management integration tests pass. Generic edit/update/delete resource routes point to missing methods; specialized approve/cancel/complete/retry actions exist. |
| Repayments | **Strong** | Financial rules, settlement, late-fee closure conditions, permissions, main page, Pending Repayments, and Repayment History pass. Static route precedence is regression-tested. |
| Late fees | **Strong** | Main page renders and late-fee calculations, paid/waived amounts, waiver permissions, and closure behavior are tested. |
| Fees | **Working with gap** | Main fee page renders and payment/receipt actions exist. `fees/members/search` points to a missing `getMembers` method. |
| Cash securities | **Strong** | Main page renders. Successful, failed, and pending mobile-money callbacks plus GL posting behavior pass tests. |
| Savings | **Working for meeting path** | Main, Pending, and Approved lists render against the legacy `datecreated` and `pdt_id` columns. Generic delete remains an unrelated legacy gap. |
| Portfolio | **Strong** | The obsolete Individual Portfolio URL and module entry were removed. Lifecycle cards map status 0 Pending, 1 Approved, 2 Active/Disbursed, 3 Closed, 4 Rejected, 5 original loan replaced, and 6 Stopped; Overdue is a derived active-loan condition. The Restructured register now lists the real `R...` replacement facilities, labels each **Restructured**, and also shows its current operational state. Branch, product, and group portfolio views use the live legacy schema. All 12 portfolio destinations return HTTP 200 and have regression coverage. |
| Operational loan reports | **Working** | Representative report renders and all ten report controller actions exist. Export accuracy was not independently reconciled against accounting records. |
| Accounting and general ledger reports | **Working** | Journal entries page renders; trial balance, balance sheet, income statement, chart of accounts, and downloads map to real controller actions. |
| UMRA regulatory reports | **Strong** | Dashboard renders. Outstanding principal/balance, PAR30, PAR90, provisions, loss exposure, closed-loan exclusion, and active-account calculations pass tests. |
| Investments | **Working** | Dashboard and investor lists render. Investor edit/update/activate/deactivate, non-destructive investment cancellation, portfolio API, and statistics API are implemented; maintenance state changes pass regression tests. |
| Expenditures and staff payout rollout | **Working** | Main page renders after correcting Blade variable initialization. Approval/payment/rollout routes remain available. |
| Public school registration and assessment | **Working** | Registration page renders; registration and assessment routes exist with throttling. |
| School administration | **Strong for meeting path** | School list and Admin Create School render. Admin creation transactionally creates both the school and its initial login account and passes a database regression test. |
| School portal: dashboard/classes/students/staff | **Working** | All four representative pages render using an approved school account. Student import/template routes exist. |
| School loans | **Working** | Loan creation page renders; approvals, disbursements, active, repayment, and portfolio routes map to real methods. |
| Access control: users/roles/permissions | **Strong** | Pages render and super-admin boundaries, administrator settings access, web-guard roles, role permission sync, and custom-role route access pass tests. |
| Agencies, branches, and field users | **Working** | Settings dashboard renders and CRUD controller methods exist. |
| System accounts and product settings | **Working with gap** | Main settings pages render. Savings-product create/show/edit routes point to missing methods; store/update/delete/toggle exist. |
| Configuration, logs, backup, and maintenance pages | **Working entry points** | Routes and views exist. Backup/maintenance execution was not performed because it could modify live state. |
| Uganda locations API | **Working structurally** | Five throttled API routes map to real controller methods; no dedicated automated API test was found. |
| Cron and payment callbacks | **Strong security posture** | Cron-secret and callback-secret behavior are tested; payment callback route is throttled and CSRF-exempt by design. |

## Broken registered routes

The route-security audit passes because permissions and middleware are correct. The following unrelated legacy actions are still registered without controller implementations.

| Area | Broken route actions |
|---|---|
| Loans | `admin.loans.destroy`, `admin.loans.history` |
| Disbursements | `admin.disbursements.edit`, `admin.disbursements.update`, `admin.disbursements.destroy` |
| Bulk SMS | `admin.bulk-sms.edit`, `admin.bulk-sms.update` |
| Fees | `admin.fees.members.search` |
| Savings | `admin.savings.destroy` |
| Savings products | `admin.savings-products.create`, `show`, `edit` |

There is also an unused legacy `admin.dashboard` Blade file that includes a nonexistent `admin.dashboard.index` view. The live dashboard uses `admin.home`, so this did not block the smoke test. The custom error page references a nonexistent `admin.dashboard` route and should be corrected to `admin.home`.

## Recommended meeting path

Use this sequence for a stable demonstration:

1. Login and open the Admin Dashboard.
2. Show the EBIMS Modules workspace and role-based visibility.
3. Open Clients, Client Applications, and Groups; group Suspend/Activate is now available.
4. Open Loan Portfolio, then Loan Approvals and Active Loans; demonstrate the Excel export, but avoid generic History/Delete actions.
5. Open Disbursements, then the main, pending, and history Repayment pages.
6. Show Late Fees, Fees, Cash Securities, and the main/pending/approved Savings pages.
7. Show Portfolio, Reports, Accounting, and UMRA dashboards.
8. Show School Administration, Admin Create School, the approved School Portal, and School Loans.
9. Show Access Control and System Settings.
10. Show Expenditures and the repaired investment maintenance actions.

## Priority before the meeting

1. Rehearse the recommended path using a super-admin account and representative data.
2. Keep the 12 unrelated legacy route gaps out of the meeting demonstration.
3. After the meeting, remove unused resource routes where specialized workflow routes already replace generic CRUD.
4. Schedule deeper mutation testing for SMS delivery, payment delivery, file uploads, and accounting reconciliation.

## Review limits

The in-app browser was unavailable, so this review does not claim pixel-level visual verification or manual clicking. The smoke script sent read-only Laravel HTTP requests against the current MySQL data. Mutation behavior for the meeting fixes was tested in isolated SQLite transactions. Destructive actions against live data, actual mobile-money transfers, SMS delivery, email delivery, file uploads, database maintenance, and live approval/payment mutations were not executed.
