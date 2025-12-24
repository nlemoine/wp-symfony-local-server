<?php

declare(strict_types=1);

namespace n5s\WpSymfonyLocalServer\Tests\Integration;

use function n5s\WpSymfonyLocalServer\redirectWpAdmin;

class RedirectWpAdminTest extends TestCase
{
    public function testRedirectsWpAdminToIndexPhp(): void
    {
        // Remove filter to test in isolation - Hooks trait auto-restores after test
        remove_filter('admin_url', 'n5s\WpSymfonyLocalServer\rewriteAdminUrl', PHP_INT_MAX);

        $requestedUrl = admin_url();
        $result = redirectWpAdmin(false, $requestedUrl);

        $this->assertSame(admin_url('index.php'), $result);
    }

    public function testRedirectsWpAdminIndexPhpToIndexPhp(): void
    {
        remove_filter('admin_url', 'n5s\WpSymfonyLocalServer\rewriteAdminUrl', PHP_INT_MAX);

        $requestedUrl = admin_url('index.php');
        $result = redirectWpAdmin(false, $requestedUrl);

        $this->assertSame(admin_url('index.php'), $result);
    }

    public function testDoesNotRedirectOtherAdminPages(): void
    {
        $requestedUrl = admin_url('plugins.php');
        $originalRedirect = 'https://example.com/redirect';

        $result = redirectWpAdmin($originalRedirect, $requestedUrl);

        $this->assertSame($originalRedirect, $result);
    }

    public function testDoesNotRedirectFrontendUrls(): void
    {
        $requestedUrl = home_url('/sample-page/');
        $originalRedirect = null;

        $result = redirectWpAdmin($originalRedirect, $requestedUrl);

        $this->assertNull($result);
    }

    public function testPreservesFalseRedirectForNonAdminUrls(): void
    {
        $requestedUrl = home_url('/blog/');

        $result = redirectWpAdmin(false, $requestedUrl);

        $this->assertFalse($result);
    }

    public function testDoesNotRedirectNestedAdminPaths(): void
    {
        $requestedUrl = admin_url('options-general.php?page=settings');
        $originalRedirect = false;

        $result = redirectWpAdmin($originalRedirect, $requestedUrl);

        $this->assertFalse($result);
    }
}
