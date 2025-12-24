<?php

declare(strict_types=1);

namespace n5s\WpSymfonyLocalServer\Tests\Integration;

use function n5s\WpSymfonyLocalServer\getHomeDir;
use function n5s\WpSymfonyLocalServer\verifySsl;

class VerifySslTest extends TestCase
{
    public function testReturnsSymfonyCertPathForLocalRequests(): void
    {
        $localUrl = home_url('/wp-json/wp/v2/posts');

        $result = verifySsl(true, $localUrl);

        $expectedPath = sprintf('%s/.symfony5/certs/rootCA.pem', getHomeDir());
        $this->assertSame($expectedPath, $result);
    }

    public function testReturnsOriginalVerifyForExternalRequests(): void
    {
        $externalUrl = 'https://api.wordpress.org/plugins/info/1.0/';

        $result = verifySsl(true, $externalUrl);

        $this->assertTrue($result);
    }

    public function testPreservesFalseVerifyForExternalRequests(): void
    {
        $externalUrl = 'https://api.wordpress.org/plugins/info/1.0/';

        $result = verifySsl(false, $externalUrl);

        $this->assertFalse($result);
    }

    public function testPreservesCustomCertPathForExternalRequests(): void
    {
        $externalUrl = 'https://api.wordpress.org/plugins/info/1.0/';
        $customCertPath = '/path/to/custom/cert.pem';

        $result = verifySsl($customCertPath, $externalUrl);

        $this->assertSame($customCertPath, $result);
    }

    public function testHandlesLocalUrlWithQueryString(): void
    {
        $localUrl = home_url('/wp-json/wp/v2/posts?per_page=10&page=1');

        $result = verifySsl(true, $localUrl);

        $expectedPath = sprintf('%s/.symfony5/certs/rootCA.pem', getHomeDir());
        $this->assertSame($expectedPath, $result);
    }
}
