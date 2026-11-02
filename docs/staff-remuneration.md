# Staff remuneration: two-table design

Reviewed 26 October 2026 (Africa/Kampala). Includes the client's clarified workbook/PDF, latest conversation amendments and explicit approval to retain a 14% bonus allocation budget with four bonuses earning at most 12%.

## Schema and ownership

Only two remuneration tables and two creation migrations are needed:

| Table | Purpose |
| --- | --- |
| `staff_remuneration_policies` | Versioned rules, effective dates, base rates, PAR threshold, budget rate, bonus bands and scoring configuration. |
| `staff_remuneration_weekly_calculations` | One officer/week, revenue and performance totals, four bonus results, budget/deficit details, original input/policy snapshots, review/approval/payment audit and `expenditure_id`. |

A policy has many weekly calculations. A calculation belongs to an officer and optionally to an existing expenditure. The existing expenditure module continues to own requests, approval, mobile-money processing and journal posting. No separate remuneration payment, budget, metric, band or deficit table is created.

The policy's bands are version-owned JSON, not separate shared entities. This is a deliberate two-table design rather than fully relational normalization of every band. Scalar amounts support filtering and reporting; immutable calculation snapshots intentionally preserve the original calculation basis. `audit_history` records saved-review revisions and payment status changes.

The creation migrations explicitly use InnoDB, and a unique key on officer/start/end prevents duplicate weeks across policy versions. `policy_id` and `expenditure_id` have foreign keys. Rates use percentage points: `12`, `3` and `10` mean 12%, 3% and 10%. Money uses two decimal places. An unresolved amount stays NULL rather than becoming an earned zero.

## Scoped installation

On a database where these tables have not yet been installed:

```powershell
php artisan migrate --path=database/migrations/2026_10_24_000001_create_staff_remuneration_policies.php --path=database/migrations/2026_10_24_000002_create_staff_remuneration_weekly_calculations.php
php artisan db:seed --class='Database\Seeders\StaffRemunerationDraftSeeder'
```

The seeder creates a draft only if its version is missing and grants the existing self-view permission to the relevant existing roles. Re-running it preserves edited or active policies and all calculations. It does not seed loans, accounts, clients or payments.

The local pre-deployment rebuild replaced only the verified empty remuneration tables, restored the existing draft with its ID and 20 bands, and removed only the named superseded remuneration migration records. Recoverable schema/data backups are under `storage/app/private/remuneration-backups/`. No general rollback, database-wide refresh, seeder or foreign-key disable was used. The old unused `expenditures.remuneration_calculation_id` column is left alone in this local database; fresh installation does not create or require it. The supported link is `staff_remuneration_weekly_calculations.expenditure_id`.

## Calculation rules checked

| Parameter | Calculation / treatment |
| --- | --- |
| Qualified revenue | Confirmed assigned-loan interest and qualifying fee collections; principal and reversed repayments excluded. Source repayment/fee IDs are saved. A missing repayment schedule blocks reconciliation. |
| Base | Current active loans / active assigned outstanding loans x 100. At least 90% earns revenue x 12%; below 90% earns revenue x 5%. Current-loan performance creates no separate bonus. |
| PAR | Oldest unpaid scheduled instalment strictly more than 120 calendar days overdue, configurable and identical across frequencies. Full outstanding principal of qualifying loans / all active assigned outstanding principal. |
| Follow-up | Distinct active assigned loans followed up during the week / active assigned loans x 100. Multiple follow-ups on the same loan count once. |
| PAR performance score | Risk <=3%: 100; >3% to 5%: 90; >5% to 8%: 75; >8% to 10%: 60; >10%: 0. These are configurable inclusive-upper-risk bands from the clarified workbook, not 100 minus risk. Apply the common bonus bands to this score. |
| Portfolio growth | Distinct active borrowing clients at closing minus opening, divided by the configured positive client-growth target, capped to 0-100%. Default target is one net new client per week, supported by the workbook. Repeat loans and replacing departing clients do not produce net growth. |
| Budget | Previous calendar period's qualified revenue x configured allocation rate (default 10%). For a Monday-Sunday week the source is the preceding Monday-Sunday week. A manual budget is required when there is no previous revenue. |
| Expenses | Every officer submits money actually received, funding evidence, dated operating costs, receipt/evidence references and completion declarations. Independent administrative approval is required. All approved officer-attributed operational expenditures must be included; linked entries are counted once, not added again. Payroll, allowances and other officers' expenses are excluded. Pending approvals or changed linked costs block payment. |
| Utilization | Approved expenses / allocated budget x 100, including values above 100%. |
| Budget score | When explicitly configured: min(100, allocated budget / approved expenses x 100). Zero expenses earn 100 only with a verified complete report, positive allowance and completed activities. Zero budget remains unresolved; it is not an automatic 100. |
| Bonus amounts | Each metric's unrounded score selects its band. Each bonus = qualified revenue x its earned rate / 100, rounded individually to two decimals; add the four individual amounts. |
| Gross | Base amount + the four individual bonus amounts. |
| Overrun | max(0, approved operating expenses - allocation). The clarified rule deducts min(overrun, gross remuneration) from gross, including base pay if necessary. Older versions retain their original bonus-only rule. |
| Deficits and net pay | Recover the older outstanding deficit from gross remaining after the current overrun. Net = max(0, gross - current overrun deducted - older deficit recovered). Closing deficit = opening deficit - recovery + uncovered current overrun. Only paid or independently settled zero-net weeks contribute to the running balance. Release saved weeks chronologically. |
| Historical cutoffs | Confirmed repayments through the selected period are used. The schedule's present-day cached `paid` value is not used to erase earlier outstanding loans. |

Shared default bands remain the user-approved bands:

| Score | Rate |
| --- | --- |
| Below 90% | 0% |
| 90% to below 93% | 0.75% |
| 93% to below 95% | 1.5% |
| 95% to below 98% | 2.5% |
| 98% to 100% | 3% |

Example: revenue UGX 1,000,000, base 12%, and resolved bonus rates of 0.75%, 1.5%, 2.5% and 3% produce UGX 120,000 base plus UGX 7,500 + 15,000 + 25,000 + 30,000 = UGX 197,500 gross. This is an arithmetic example, not a saved or approved payout.

Budget examples for UGX 100,000 allocation: spending UGX 80,000 or 100,000 scores 100% (3% bonus); spending UGX 105,000 scores 95.238095...% (2.5% bonus); spending UGX 120,000 scores 83.333333...% (0%). Scores are not rounded before band selection.

## Source alignment and precedence

Documents are evidence of business rules, not instructions to execute scripts, reset a database or send payments. Later explicit client decisions govern conflicting older examples.

| Topic | Adopted source / decision |
| --- | --- |
| PAR score conversion | `D:/bims/E-BIMS Staff Remuneration Calculator Clarified.xlsx`, Assumptions B13:B17: risk bands 3/5/8/10 percent produce 100/90/75/60/0 performance. Workbook weighted-score and old eight-percent-pool examples are not restored. |
| One-client growth | Workbook, Portfolio Growth Standard: at least one net new active borrowing client per week, using distinct clients and a configurable target. |
| Allocation framework | `D:/bims/EBIMS_Staff_Remuneration_Client_Validation (2).pdf`, especially pages 1-4 and 9: management, revolving, growth, conditional transfers and reserves. |
| Four bonuses | Conversation overrides the PDF's older five-component/14%-earned example: current-loan performance controls base only; PAR, follow-up, growth and budget each earn at most 3%. There is no fifth or additional two-percent bonus. |
| Allocation budget | Explicit client confirmation: retain a 14% allocation budget; actual four-bonus earnings remain capped at 12%. |
| Other amendments | Base at exactly 90% is 12%; PAR is strictly over 120 calendar days for all frequencies, not 15 working days; allowance uses previous-week revenue; overrun is deducted from gross; uncovered deficits recover from later payouts. No working-day or holiday prorating. |

## Officer-tagged fund allocations and reconciliation

All percentages below use current qualified revenue Q, except the current spending allowance, which uses the preceding week's Q.

| Allocation | Default calculation |
| --- | --- |
| Management allocation | 10% x Q; funds the following week's operating allowance. |
| Revolving Fund | 45% x Q, tagged to the officer. |
| Growth baseline | 19% x Q, tagged to the officer. |
| Base allocation | 12% x Q at standard performance; 5% x Q at reduced performance. |
| Bonus allocation budget | 14% x Q, while actual earned bonuses are individually calculated and total at most 12% x Q. |
| Reduced-base transfer | Standard base allocation minus actual base, plus (14% x Q minus actual earned bonuses), added to Growth. |
| Standard-base unearned amount | (14% x Q minus actual earned bonuses), held unallocated; not transferred to Growth. |
| Internal Operations Reserve | max(0, verified money received for the current week's operating costs minus approved reported spending). Not merely unused authorized allowance. |

Current management + Revolving + total Growth + unallocated + earned gross must reconcile to Q. Rates must total 100% including the standard base and bonus budget. Cent-rounding differences up to three cents are explicitly recorded in the unallocated rounding adjustment only if the resulting holding stays non-negative; otherwise reconciliation remains blocked. Larger differences block release.

Reserve transfers reclassify earlier received operating funds; they are not counted a second time as an allocation of current Q. A partially funded allowance therefore does not create a fictitious reserve. For example, allowance 100,000, actual receipt 60,000 and spending 40,000 leave 20,000 received funds for reserve, not 60,000. The 40,000 unfunded allowance is not money available for transfer. The overrun test remains spending versus the approved allowance, not versus cash received.

Amounts earned but withheld for current expenses or prior deficits are NOT unearned bonuses and must not be transferred to Growth a second time. Gross is the revenue-allocation basis; net is the mobile-money payout. The expense report is not a second payable or an automatic reimbursement request. Existing expenditure accounting remains responsible for actual expense recognition and funding; allocation journals are non-cash fund earmark/reclassification entries.

Example with Q 1,000,000 and all four scores at 100: base 120,000 + bonuses 120,000 = gross 240,000; management 100,000 + RF 450,000 + Growth 190,000 + unallocated 20,000 + gross 240,000 = 1,000,000. If 100,000 was received for current expenses and 80,000 spent, reserve transfer is a separate 20,000 from earlier operating funds.

Reduced-base example: Q 1,000,000, base 50,000, earned bonuses 60,000. Growth is 190,000 baseline + 70,000 base difference + 80,000 unearned bonus budget = 340,000. Any subsequent gross-pay deduction does not change these earned/unearned amounts.

Deficit example: gross 20,000 and overrun 40,000 produce zero net and a new 20,000 deficit. A later gross 12,000 with no overrun recovers 12,000, leaves zero net and carries 8,000. No negative payout or duplicate recovery is created.

## Weekly operating workflow

1. An authorized policy administrator edits or creates a draft version, including base/bonus bands, PAR conversion, growth target, budget method, allocations and six existing GL accounts. Referenced or approved rules are not rewritten: create a new version. Policy changes record actor, timestamp, changed fields and prior rules in version-owned audit JSON.
2. Configure six distinct active non-cash accounts before approving the policy: distributable revenue/earnings source (Income or Equity), management holding, RF holding, Growth holding, Internal Operations Reserve holding and unallocated holding (the five holdings use Liability or Equity). The application does not guess accounts or create balances. Approval and activation are explicit admin actions.
3. Officers use **My remuneration performance**, select a week and report amounts actually received and used, with funding/expense references. Existing operating expenditure IDs can be linked. A zero-spending report still needs verified completed activities. Monthly performance is a view of complete Monday-Sunday weeks whose Monday falls in that month, not a new monthly payroll formula.
4. An independent administrator verifies funding, all operating costs, completeness and activity reporting, then approves or returns the report. A returned report can be corrected; an approved report must be returned before changing it. Reports and approval history are kept within the officer's one weekly calculation row.
5. Admin reviews the all-officer filtered performance screen and individual bonus amounts. Saving refreshes reviewable snapshots without creating payment. No previous-week revenue requires an explicitly recorded manual allowance; a zero allowance is not silently scored.
6. Release only a closed week, fully resolved under an active policy, after earlier saved weeks are paid/settled. Recalculate and compare key source totals before creating the payout request. Saved approval/payment states cannot be overwritten by preview refreshes.
7. Positive net creates an existing expenditure for approval; zero net has a separate independent settlement action to retain the week's deficit without manufacturing a zero-value mobile-money request. General expense creation does not accept `performance_payout`. Both approval and payment validate the current calculation link, readiness, officer, week and exact saved net amount. Unlinked legacy staff requests remain preserved but cannot be approved or sent as new remuneration. Existing maker-checker controls remain in force.
8. After approval, post fund allocations explicitly. A balanced journal is tagged to officer and calculation, records the policy version, locks the configured accounts and checks management holding coverage for reserve reclassification. Retrying returns the existing non-reversed journal. No reserve withdrawal endpoint is provided.
9. Mobile-money payment uses the saved net remuneration only after its allocation journal is posted. The existing investment funding, approval, provider reference, status-check, retry and final-payment journal workflow remains in use. A journal posting is not a mobile-money transfer. Completed/approved officer weekly and monthly displays use saved snapshots rather than recalculating history with newer policy rules.

### Routes and implementation ownership

| Component | Responsibility |
| --- | --- |
| `WeeklyRemunerationPreviewService` | All amounts/scores, source IDs, individual bonuses, overrun/deficit calculations, fund reconciliation and persistence/display snapshots. |
| `WeeklyRemunerationWorkflowService` | Report submission/review, locked and idempotent payout-request creation, independent zero-net settlement, rejected-request reopening, release checks, account validation and officer fund posting. |
| `StaffRemunerationPerformanceController` | Self-only week/month views and `POST /admin/remuneration/spending-report`; officer identity comes from the signed-in user. |
| `StaffRemunerationController` | Filtered admin previews/saving, report review, expenditure request, fund posting and zero-net settlement. |
| `StaffRemunerationPolicyController` | Permission-controlled versioned rule setup, approval, activation and non-destructive retirement. |
| `ExpenditureController` | Existing independent approval and mobile-money workflow, saved-calculation status sync, required allocation-posting guard. |
| Admin calculation POST routes | Existing remuneration calculation route plus `/{calculation}/spending-report`, `/{calculation}/funds`, `/{calculation}/settle-zero`. |

No new remuneration tables or alteration migrations were added for these features. Weekly reports, funding evidence references, detailed deductions, fund statement/posting reference and audit history use the two existing remuneration tables and existing general ledger tables.

### Screen and route coverage after cleanup

Paths below are relative to `/admin/expenditures`, except the officer self-service routes.

| Screen or action | Route / method | Controller and view |
| --- | --- | --- |
| All-officer weekly review and selected officer detail | `GET /remuneration`; `POST /remuneration/calculations` saves a review | `StaffRemunerationController`; `staff-remuneration/index` and shared breakdown/report/allocation partials |
| Officer weekly and monthly performance | `GET /admin/remuneration/performance`; `POST /admin/remuneration/spending-report` | `StaffRemunerationPerformanceController`; `staff-remuneration/performance`, with self-only report submission |
| Policy list, create/copy, details and edit | `GET /remuneration/policies`, `/create`, `/{policy}`, `/{policy}/edit`; `POST` create and `PUT` update | `StaffRemunerationPolicyController`; `policies/index`, `form`, `show` |
| Policy approval, activation and retirement | `POST /remuneration/policies/{policy}/approve`, `/activate`; `DELETE /remuneration/policies/{policy}` | Existing setup-screen actions; retirement is a state change, not row deletion |
| Report review, payout request, fund posting, zero-net settlement and rejected-request reopening | `POST /remuneration/calculations/{calculation}/spending-report`, `/payout`, `/funds`, `/settle-zero`, `/reopen` | Actions on the admin officer-detail screen delegate to the workflow service; no duplicate action-specific screens |
| Expense approval, rejection, mobile-money initiation, retry and status | Existing expenditure detail/payment routes | `ExpenditureController`; existing `expenditures/show` and payment/status workflow |
| Legacy payroll history | `GET /rollout/{rollout}` | `ExpenditureController::showRollout`; existing `expenditures/rollout-show`, displaying saved values only |

The three remuneration controllers, two remuneration models and two focused services are retained. The calculator owns formulas, source attribution and snapshots; the workflow service owns state transitions and posting. Existing expenditure, user, loan, repayment, follow-up, account and journal models remain the source records. A single oversized service is not required to remove duplication.

### Duplication and historical safeguards

- The unreachable legacy wage/stewardship calculator and unused `expenditures/rollout` generator view were removed. The deleted view is tracked in Git and recoverable. Legacy GET entry redirects to remuneration review; legacy generation/approval/payment POST endpoints return HTTP 410. Read-only history, its models and its tables remain available. Missing historical amounts are displayed as not captured, not reconstructed with current or former defaults.
- Policy identity, metric labels and default bonus/PAR bands now have one definition in `StaffRemunerationPolicy`. Both admin and officer previews use the same policy-selection method: active policy first, otherwise latest draft for review only. This does not approve or activate a draft.
- Officer/period lookup and the reviewable-state guard are shared by the calculation model. Database uniqueness prevents a second officer/week or a second calculation linked to the same expenditure. Workflow transactions lock the officer and saved calculation; repeated payout requests and fund postings return the existing result. Overlapping unreconciled payouts are refused.
- Policy update, approval, activation and retirement recheck the locked version state inside their transactions. Activation locks existing versions in a consistent order and retires the previous active version. Referenced drafts cannot be overwritten; copying creates a new policy version instead.
- Ordinary expenditures and remuneration requests use the same expense-number generator and existing unique expense-number constraint. Payment requests never independently recalculate or accept a caller-supplied net amount.
- Removed duplicate validation-error rendering and unused scoring-JSON inputs that did not affect calculations. Policy dates use HTML date values, PAR maximum input agrees with backend validation, and the weekly review end date is derived from Monday rather than suggesting an arbitrary payroll period. Invalid monthly filters and malformed report rows are rejected before processing.
- Original policy/input/payment snapshots and request-history JSON are intentional audit copies, not duplicate payable records. They are preserved. This cleanup adds no migration and does not drop, reseed or rewrite application data.

## Remaining go-live checks

- The clarified policy is seeded as a NEW draft (`2026-10-26-client-clarified`). The authorized local account mappings are now configured as listed below; policy approval and activation remain the administrator's explicit next actions. Original draft/history remains untouched. Tests activate isolated test policies only, never the application policy or payment provider.
- Administrators must verify funding evidence and original receipts; text references are not independent proof of a disbursement or an expense. No new attachment storage or bank/provider reconciliation subsystem is implied.
- Revenue attribution still uses the loan's current assignee and fee labels. Historic reassignment, closed/restructured loans, excess repayments versus actual late fees, duplicate collection source documents and incomplete schedules need source-data review before certifying payroll. This implementation does not invent an assignment history or silently repair transactions.
- Accounting must reconcile management-holding balances with existing budget funding and actual expense postings. Allocation posting does not book a duplicate operating expense or automatically pay operating advances.
- A rejected/cancelled unpaid payout now has an explicit, reason-required **Reopen rejected payout for correction** action. It preserves the old expenditure, archives its ID/amount/reason and prior calculation snapshots, reverses any posted allocation journal with an independent administrator, then detaches only the current expenditure pointer. The original journal and its balanced reversal remain available. A refreshed review is mandatory before a replacement request. Pending, paid, funding-reserved or otherwise unreconciled requests cannot use this action. Rejected/replaced provider references cannot revive the old request through the status endpoint. The full request history stays in the same weekly calculation JSON; no extra remuneration table is needed.

## Authorized local ledger setup and final handoff

Client authorization: create five zero-balance Equity holdings under Retained Earnings; credit account setup only to Super Admin (user ID 1). The client will approve and activate the policy personally through the setup screen. Creating accounts is not policy approval and does not post opening balances.

| Role | Account ID | Display code | Name |
| --- | --- | --- | --- |
| Source | 82 | 32000 | Retained Earnings (existing) |
| Management | 96 | 32010 | Management Budget |
| Revolving | 97 | 32020 | Revolving Fund |
| Growth | 98 | 32030 | Growth Revolving Fund |
| Operations reserve | 99 | 32040 | Internal Operations Reserve |
| Unallocated | 100 | 32050 | Unallocated Bonus |

All six balances were zero after setup. Existing financial entries were not changed. Local policy ID 2 remains `draft`, with `approved_by` and `approved_at` NULL. Use `/admin/expenditures/remuneration/policies` and select **2026-10-26-client-clarified**, review its mappings/rules, then click **Approve** and **Activate**. Do not activate the retained older draft accidentally.

The scoped command is preview-only unless `--apply` is supplied. On other databases select their actual source and authorized creator IDs; the local IDs above are not universal:

```powershell
php artisan remuneration:configure-ledger --source=82 --actor=1
php artisan remuneration:configure-ledger --source=82 --actor=1 --apply
```

It accepts only an unused draft and an authorized active administrator for account creation, reuses its existing mapped holdings on repeat runs, refuses to overwrite different mappings, and never approves/activates the policy. It backs up the policy and entire account list before applying changes. The successful local setup backup is `storage/app/private/remuneration-backups/before-ledger-setup-20261026-101856-b15b38.json`. The legacy chart requires an `added_by` value despite the fresh-install schema permitting NULL; the command explicitly records the authorized creator and preserves the manual approval boundary.

The acceptance test also identified and fixed a legacy funding issue: `investment.amount` is VARCHAR. The atomic funding check now casts it to DECIMAL before comparison, so balances such as `1000000` correctly cover a `240000` payout. Failed preparation synchronizes remuneration/payment status; retries reserve funding once, terminal failure releases it, and final confirmation does not deduct or journal it twice.

## Verification

```powershell
vendor\bin\phpunit tests/Feature/StaffRemunerationTest.php tests/Feature/StaffRemunerationPaymentFlowTest.php tests/Unit/RouteSecurityAuditTest.php --no-coverage
```

The remuneration suite creates its own explicitly isolated SQLite in-memory connection. It runs only the two remuneration creation migrations against minimal test dependencies and checks fresh installation, safe reseeding, bonus boundaries, previous-week attribution, budget scores, expense exclusions, strict PAR boundaries/frequencies and score conversion, follow-ups, distinct clients/one-client growth, historical cutoffs, frozen snapshots, idempotent saving, protected payment states, complete/zero-spending report approval, self-review protection, duplicate receipts, gross overrun recovery across weeks, 14%-versus-12% reconciliation, reduced-base transfers and balanced/idempotent officer-tagged GL entries. It never refreshes the live database or invokes a real mobile-money transfer.

26 October verification: 57 tests / 605 assertions passed; Blade compilation and the new spending-report, administrative-review and allocation-statement render checks passed. All 17 remuneration routes are registered; the route-security audit passed. This is automated verification, not a claim that a real provider transfer or browser-based end-to-end payment was performed.

Additional acceptance suite: `StaffRemunerationPaymentFlowTest` uses an explicitly isolated SQLite memory connection and the real relevant expenditure, ledger and remuneration migrations, avoiding unrelated legacy-data migrations. Real route middleware and controllers remain enabled; actor changes simulate separate authenticated sessions. HTTP/provider calls are blocked unless mocked. Coverage includes manual/forged payout rejection, unlinked legacy requests, report verification, policy approval/activation in the test database, independent payment authorization, rejected-request replacement, allocation reversal, funding/provider failure, retry, pending and immediate/final confirmation, saved-net-only transfer, historical request protection, duplicate prevention and normal operating-expense regression. No real provider transfer was performed.

Final hardening verification: 69 tests / 836 assertions, 18 remuneration routes, PHP lint and Blade compilation. The rejected-request correction card and request-history partial are rendered in acceptance tests. Two remuneration tables remain installed (batch 34); zero live weekly calculations or payments were created. Expenditures, raw payments, repayments, fees and journal counts/checksums remained equal to the pre-setup snapshot. The only intended business-data changes were five zero-balance account rows and account mappings/audit metadata in clarified draft ID 2. Approval/activation are explicitly reserved for the user's setup-screen actions; a real-provider sandbox/live integration test has not been performed.

Subsequent whole-flow cleanup verification: **76 tests / 920 assertions passed**, with 18 remuneration routes registered and Blade templates compiled successfully. Added acceptance coverage exercises every admin/policy/officer week/month screen through authenticated routes, policy create/update/retire and single-active-version transitions, referenced-draft protection, invalid report/month inputs, repeated HTTP payout/fund requests and retired legacy routes plus read-only historical rollout display. The provider remains mocked. Browser visual inspection could not run because no connected browser was available; authenticated rendering tests are not a substitute for visual QA.

The cleanup's live read-only audit compared count/checksum pairs for 111 tables against `storage/app/private/remuneration-backups/after-payment-hardening-20261026-102435.json`. The only changed table was `sessions` (login-session data). All business tables, account mappings and both draft policies were unchanged. Exactly the two remuneration creation migrations remain recorded in batch 34; weekly calculations remain zero, and neither policy has an approval actor/timestamp. No migration, live seeder, policy approval, allocation or payment was executed for this cleanup.

Local database audit: `ebims1` contained 111 non-cache tables, including exactly two remuneration tables. Before/after count and checksum comparisons changed only `staff_remuneration_policies` when the clarified draft was inserted. The original policy row was byte-for-byte equivalent in exported JSON. Weekly calculations remained zero. Expenditures 7, raw payments 1,197, repayments 2,120, personal loans 174, schedules 2,550, fees 461, journals 1,924 and journal lines 7,688 were unchanged. Audit snapshots are `storage/app/private/remuneration-backups/client-alignment-before-20261026-091743.json` and `client-alignment-after-20261026-091803.json`. No table was dropped or rebuilt for this alignment, and no approval, allocation journal or payment was posted in the application database.
