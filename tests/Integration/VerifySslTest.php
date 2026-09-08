<?php

declare(strict_types=1);

namespace n5s\WpSymfonyLocalServer\Tests\Integration;

use n5s\WpSymfonyLocalServer\Tests\Integration\Concerns\SymfonyHomeFixture;
use function n5s\WpSymfonyLocalServer\verifySsl;

class VerifySslTest extends TestCase
{
    use SymfonyHomeFixture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSymfonyHome();
    }

    protected function tearDown(): void
    {
        $this->tearDownSymfonyHome();

        parent::tearDown();
    }

    public function testReturnsSymfonyCertPathForLocalRequests(): void
    {
        $certPath = $this->installCertFixture();

        $result = verifySsl(true, home_url('/wp-json/wp/v2/posts'));

        $this->assertSame($certPath, $result);
    }

    public function testFallsBackToVerifyWhenCertIsMissing(): void
    {
        // No cert fixture installed: getSymfonyHomeDir() resolves under $tmpHome and
        // rootCA.pem doesn't exist there, so verifySsl must return $verify unchanged.
        // This is the fix from commit 77b1c7d: prevents cURL error 77 before the user
        // has run `symfony server:ca:install`.
        $result = verifySsl(true, home_url('/wp-json/wp/v2/posts'));

        $this->assertTrue($result);
    }

    public function testFallsBackToCustomCertWhenSymfonyCertIsMissing(): void
    {
        $customCert = '/path/to/custom/cert.pem';

        $result = verifySsl($customCert, home_url('/wp-json/wp/v2/posts'));

        $this->assertSame($customCert, $result);
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
        $certPath = $this->installCertFixture();

        $result = verifySsl(true, home_url('/wp-json/wp/v2/posts?per_page=10&page=1'));

        $this->assertSame($certPath, $result);
    }
}
