<?php

namespace Tests\Unit;

use App\Support\RouteSecurityAudit;
use Tests\TestCase;

class RouteSecurityAuditTest extends TestCase
{
    public function test_route_security_audit_has_no_issues(): void
    {
        $this->assertSame([], app(RouteSecurityAudit::class)->issues()->all());
    }

    public function test_operational_route_permissions_are_explicit(): void
    {
        foreach (array_keys(config('ebmis_permissions.route_permissions', [])) as $routeName) {
            $this->assertStringNotContainsString('*', $routeName);
        }
    }

    public function test_sensitive_financial_mutations_require_super_admin_middleware(): void
    {
        foreach (config('ebmis_permissions.sensitive_super_admin_routes', []) as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, 'Route not found: ' . $routeName);
            $this->assertContains('super_admin', $route->gatherMiddleware(), 'Route must require super_admin: ' . $routeName);
        }
    }

    public function test_staff_payment_rollout_requires_staff_payment_rollout_middleware(): void
    {
        foreach (config('ebmis_permissions.sensitive_staff_payment_rollout_routes', []) as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, 'Route not found: ' . $routeName);
            $this->assertContains('staff_payment_rollout', $route->gatherMiddleware(), 'Route must require staff_payment_rollout: ' . $routeName);
        }
    }

    public function test_cron_endpoint_rejects_requests_when_secret_is_not_configured(): void
    {
        config(['app.cron_secret' => null]);

        $this->get('/cron/run')->assertForbidden();
    }

    public function test_mobile_money_callback_rejects_missing_secret_when_enforcement_is_enabled(): void
    {
        config([
            'flexipay.require_callback_secret' => true,
            'flexipay.callback_secret' => 'test-callback-secret',
        ]);

        $this->postJson('/admin/mobile-money/callback', [
            'transactionReferenceNumber' => 'TEST-CALLBACK',
            'statusCode' => '00',
        ])->assertForbidden();
    }

    public function test_mobile_money_callback_accepts_configured_secret_before_processing_payload(): void
    {
        config([
            'flexipay.require_callback_secret' => true,
            'flexipay.callback_secret' => 'test-callback-secret',
        ]);

        $this->postJson('/admin/mobile-money/callback?token=test-callback-secret', [
            'transactionReferenceNumber' => 'TEST-CALLBACK',
            'statusCode' => '00',
        ])->assertStatus(400);
    }
}
