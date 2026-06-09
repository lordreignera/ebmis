# Route Security Audit

EBMIS operational admin routes use two middleware layers:

- `ebims_module` checks whether the signed-in user may enter the operational workspace.
- `ebmis_permission` resolves an explicit route name in `config/ebmis_permissions.php` and checks the assigned permission.

The permission middleware is fail-closed. A newly added operational admin route returns `403` until its route name is deliberately added to `route_permissions`.

High-risk financial mutations, such as loan disbursement, loan stopping, manual closure, late-fee waivers, manual repayment edits, and manual fee confirmation, also require `super_admin`.

The audit also verifies the public application allowlist, rate limits on public submissions, and authenticated `approved_school` protection for school workspace routes.

Run the audit after changing routes or permissions:

```bash
php artisan security:audit-routes
php artisan security:audit-routes --matrix
```

Before enabling provider callback enforcement in production, set:

```dotenv
FLEXIPAY_CALLBACK_SECRET=use-a-long-random-secret
FLEXIPAY_REQUIRE_CALLBACK_SECRET=true
FLEXIPAY_CALLBACK_URL="${APP_URL}/admin/mobile-money/callback?token=${FLEXIPAY_CALLBACK_SECRET}"
```
