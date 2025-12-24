<?php

declare(strict_types=1);

namespace n5s\WpSymfonyLocalServer\Tests\Integration;

use function n5s\WpSymfonyLocalServer\rewriteAdminUrl;

class RewriteAdminUrlTest extends TestCase
{
    public function testAppendsIndexPhpWhenPathIsEmpty(): void
    {
        $baseAdminUrl = rtrim(admin_url(), '/');

        $result = rewriteAdminUrl($baseAdminUrl, '', null, 'admin');

        $this->assertSame($baseAdminUrl . '/index.php', $result);
    }

    public function testDoesNotModifyUrlWithPath(): void
    {
        $url = admin_url('plugins.php');

        $result = rewriteAdminUrl($url, 'plugins.php', null, 'admin');

        $this->assertSame($url, $result);
    }

    public function testDoesNotModifyUrlWithNestedPath(): void
    {
        $url = admin_url('options-general.php');

        $result = rewriteAdminUrl($url, 'options-general.php', null, 'admin');

        $this->assertSame($url, $result);
    }

    public function testHandlesQueryStringOnlyPath(): void
    {
        $baseAdminUrl = rtrim(admin_url(), '/');
        $path = '?page=settings';
        $url = $baseAdminUrl . '/' . $path;

        $result = rewriteAdminUrl($url, $path, null, 'admin');

        $this->assertStringContainsString('index.php', $result);
    }

    public function testHandlesTrailingSlashInBaseUrl(): void
    {
        $baseAdminUrl = admin_url();

        $result = rewriteAdminUrl($baseAdminUrl, '', null, 'admin');

        $this->assertStringEndsWith('/index.php', $result);
        $this->assertStringNotContainsString('//index.php', $result);
    }

    public function testPreservesHttpsScheme(): void
    {
        $url = admin_url();

        $result = rewriteAdminUrl($url, '', null, 'https');

        $this->assertStringStartsWith('http', $result);
    }

    public function testHandlesBlogIdParameter(): void
    {
        $url = admin_url();

        $result = rewriteAdminUrl($url, '', 1, 'admin');

        $this->assertStringEndsWith('/index.php', $result);
    }
}
