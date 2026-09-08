<?php

declare(strict_types=1);

namespace n5s\WpSymfonyLocalServer\Tests\Integration\Concerns;

use RuntimeException;
use function n5s\WpSymfonyLocalServer\getSymfonyHomeDir;
use function n5s\WpSymfonyLocalServer\getUserConfigDir;

/**
 * Points getSymfonyHomeDir() at a throwaway HOME so tests never read the developer's
 * real Symfony CLI config directory (which would make them pass or fail depending on
 * whether `symfony server:ca:install` has been run on the machine).
 */
trait SymfonyHomeFixture
{
    /**
     * @var list<string>
     */
    private const SYMFONY_HOME_ENV_KEYS = ['HOME', 'XDG_CONFIG_HOME', 'APPDATA'];

    private string $tmpHome = '';

    /**
     * @var array<string, array{server: mixed, env: mixed}>
     */
    private array $envBackup = [];

    protected function setUpSymfonyHome(): void
    {
        $this->tmpHome = sys_get_temp_dir() . '/wp-symfony-test-' . uniqid('', true);
        mkdir($this->tmpHome, 0700, true);

        foreach (self::SYMFONY_HOME_ENV_KEYS as $key) {
            $this->envBackup[$key] = [
                'server' => array_key_exists($key, $_SERVER) ? $_SERVER[$key] : null,
                'env' => array_key_exists($key, $_ENV) ? $_ENV[$key] : null,
            ];
            unset($_SERVER[$key], $_ENV[$key]);
        }

        $_SERVER['HOME'] = $this->tmpHome;
        $_SERVER['XDG_CONFIG_HOME'] = $this->tmpHome . '/.config';
    }

    protected function tearDownSymfonyHome(): void
    {
        foreach (self::SYMFONY_HOME_ENV_KEYS as $key) {
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
    }

    /**
     * Create a Symfony CLI directory (`symfony-cli`, `symfony5`, …) inside the throwaway
     * OS config dir and return its path.
     */
    protected function makeConfigDir(string $dirName): string
    {
        $dir = getUserConfigDir() . '/' . $dirName;
        mkdir($dir, 0700, true);

        return $dir;
    }

    /**
     * Create the legacy ~/.symfony5 directory inside the throwaway HOME and return its path.
     */
    protected function makeLegacyHomeDir(): string
    {
        $dir = $this->tmpHome . '/.symfony5';
        mkdir($dir, 0700, true);

        return $dir;
    }

    /**
     * Write a fake rootCA.pem where getSymfonyHomeDir() resolves and return its path.
     */
    protected function installCertFixture(): string
    {
        $symfonyHome = getSymfonyHomeDir();
        if (! str_starts_with($symfonyHome, $this->tmpHome)) {
            throw new RuntimeException(sprintf(
                'Refusing to write a cert fixture outside the throwaway HOME: %s',
                $symfonyHome
            ));
        }

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
