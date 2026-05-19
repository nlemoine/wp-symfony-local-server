<?php

declare(strict_types=1);

namespace n5s\WpSymfonyLocalServer\Tests\Integration;

use function n5s\WpSymfonyLocalServer\getSymfonyHomeDir;
use function n5s\WpSymfonyLocalServer\verifySsl;

class VerifySslTest extends TestCase
{
    private const ENV_KEYS = ['HOME', 'XDG_CONFIG_HOME', 'APPDATA'];

    private string $tmpHome = '';

    /** @var array<string, array{server: mixed, env: mixed}> */
    private array $envBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpHome = sys_get_temp_dir() . '/wp-symfony-test-' . uniqid('', true);
        mkdir($this->tmpHome, 0700, true);

        foreach (self::ENV_KEYS as $key) {
            $this->envBackup[$key] = [
                'server' => array_key_exists($key, $_SERVER) ? $_SERVER[$key] : null,
                'env' => array_key_exists($key, $_ENV) ? $_ENV[$key] : null,
            ];
            unset($_SERVER[$key], $_ENV[$key]);
        }

        $_SERVER['HOME'] = $this->tmpHome;
        $_SERVER['XDG_CONFIG_HOME'] = $this->tmpHome . '/.config';
    }

    protected function tearDown(): void
    {
        foreach (self::ENV_KEYS as $key) {
            $backup = $this->envBackup[$key];
            if ($backup['server'] === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $backup['server'];
            }
            if ($backup['env'] === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $backup['env'];
            }
        }

        $this->removeDirectoryRecursively($this->tmpHome);

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

    private function installCertFixture(): string
    {
        $symfonyHome = getSymfonyHomeDir();
        $this->assertStringStartsWith($this->tmpHome, $symfonyHome, 'fixture must resolve under tmpHome');

        $certDir = $symfonyHome . '/certs';
        mkdir($certDir, 0700, true);
        $certPath = $certDir . '/rootCA.pem';
        file_put_contents($certPath, "fake test cert\n");

        return $certPath;
    }

    private function removeDirectoryRecursively(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $entries = scandir($dir);
        if ($entries === false) {
            return;
        }

        foreach (array_diff($entries, ['.', '..']) as $entry) {
            $path = $dir . '/' . $entry;
            if (is_dir($path) && ! is_link($path)) {
                $this->removeDirectoryRecursively($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
