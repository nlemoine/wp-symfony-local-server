<?php

declare(strict_types=1);

namespace n5s\WpSymfonyLocalServer\Tests\Integration;

use function n5s\WpSymfonyLocalServer\isSymfonyLocalServer;

/**
 * Tests that hooks are registered when Symfony Local Server is detected.
 *
 * These tests only run when Symfony CLI is installed and configured.
 * Function logic tests in other files run regardless.
 */
class HookRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! isSymfonyLocalServer()) {
            $this->markTestSkipped('Symfony Local Server not detected - skipping hook registration tests.');
        }
    }

    public function testPreHttpSendThroughProxyFilterIsRegistered(): void
    {
        $this->assertNotFalse(
            has_filter('pre_http_send_through_proxy', 'n5s\WpSymfonyLocalServer\shouldSendThroughProxy')
        );
    }

    public function testHttpsSslVerifyFilterIsRegistered(): void
    {
        $this->assertNotFalse(
            has_filter('https_ssl_verify', 'n5s\WpSymfonyLocalServer\verifySsl')
        );
    }

    public function testRedirectCanonicalFilterIsRegistered(): void
    {
        $this->assertNotFalse(
            has_filter('redirect_canonical', 'n5s\WpSymfonyLocalServer\redirectWpAdmin')
        );
    }

    public function testWpRedirectStatusFilterIsRegistered(): void
    {
        $this->assertNotFalse(
            has_filter('wp_redirect_status', 'n5s\WpSymfonyLocalServer\redirectWpAdminStatus')
        );
    }

    public function testAdminUrlFilterIsRegistered(): void
    {
        $this->assertNotFalse(
            has_filter('admin_url', 'n5s\WpSymfonyLocalServer\rewriteAdminUrl')
        );
    }

    public function testAdminUrlFilterIsAppliedOnce(): void
    {
        $this->expectApplied('admin_url')->once();

        admin_url();
    }

    public function testAdminUrlFilterReturnsString(): void
    {
        $this->expectApplied('admin_url')->andReturnString();

        admin_url();
    }

    public function testRequestIsFakedAndRecorded(): void
    {
        $this->fake_request(home_url('/test-endpoint'));

        wp_remote_get(home_url('/test-endpoint'));

        $this->assertRequestSent(home_url('/test-endpoint'));
    }
}
