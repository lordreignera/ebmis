<?php

namespace Tests\Feature;

use Tests\TestCase;

class ClientLoanApplicationPublicFlowTest extends TestCase
{
    public function test_apply_token_mismatch_has_friendly_redirect_fallback(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

        $this->assertStringContainsString("request()->is('apply')", $bootstrap);
        $this->assertStringContainsString("request()->isMethod('post')", $bootstrap);
        $this->assertStringContainsString("redirect()->route('client.apply')", $bootstrap);
        $this->assertStringContainsString("withInput(request()->except('_token'))", $bootstrap);
        $this->assertStringContainsString('Your application session expired before it was submitted.', $bootstrap);
    }

    public function test_apply_form_refreshes_csrf_before_submit_and_keeps_draft_until_success(): void
    {
        $view = file_get_contents(resource_path('views/client/apply.blade.php'));

        $this->assertStringContainsString('function refreshCsrfToken()', $view);
        $this->assertStringContainsString('route("csrf-token.refresh")', $view);
        $this->assertStringContainsString('MAX_UPLOAD_BYTES = 5 * 1024 * 1024', $view);
        $this->assertStringContainsString('Submitting...', $view);
        $this->assertStringNotContainsString('localStorage.removeItem(DRAFT_PREFIX', $view);
    }
}
