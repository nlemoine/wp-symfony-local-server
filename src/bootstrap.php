<?php

declare(strict_types=1);

namespace n5s\WpSymfonyLocalServer;

use n5s\WpHookKit\Hook;

/**
 * Get home directory.
 */
function getHomeDir(): string
{
    /** @var string|null */
    static $homeDir;
    if (isset($homeDir)) {
        return $homeDir;
    }

    $home = getenv('HOME');
    if (empty($home)) {
        if (! empty($_SERVER['HOMEDRIVE']) && is_string($_SERVER['HOMEDRIVE']) && ! empty($_SERVER['HOMEPATH']) && is_string($_SERVER['HOMEPATH'])) {
            // home on windows
            $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
        }
    }

    if (empty($home)) {
        $home = posix_getpwuid(posix_geteuid())['dir'] ?? '';
    }

    return $homeDir = rtrim((string) $home, '/');
}

/**
 * Get Symfony Local Server configuration.
 *
 * @return array{tld: string, host: string, port: int, domains: array<string, string>}|array{}
 */
function getSymfonyConfig(): array
{
    /** @var array{tld: string, host: string, port: int, domains: array<string, string>}|array{}|null $symfonyConfig */
    static $symfonyConfig;
    if (isset($symfonyConfig)) {
        return $symfonyConfig;
    }

    $symfonyconfigPath = sprintf('%s/.symfony5/proxy.json', getHomeDir());
    if (! is_file($symfonyconfigPath) || ! is_readable($symfonyconfigPath)) {
        return $symfonyConfig = [];
    }

    $json = file_get_contents($symfonyconfigPath);
    if ($json === false) {
        return $symfonyConfig = [];
    }

    $config = json_decode(
        json: $json,
        associative: true,
        flags: JSON_THROW_ON_ERROR
    );

    if (! is_array($config)) {
        return $symfonyConfig = [];
    }

    return $symfonyConfig = $config;
}

/**
 * Get Symfony Local Server proxy port.
 */
function getSymfonyProxyPort(): int
{
    return (int) (getSymfonyConfig()['port'] ?? 7080);
}

/**
 * Get Symfony Local Server proxy TLD.
 */
function getSymfonyProxyTld(): string
{
    return getSymfonyConfig()['tld'] ?? 'wip';
}

/**
 * Get Symfony Local Server proxy domains.
 *
 * @return array<string, string>
 */
function getSymfonyProxyDomains(): array
{
    return getSymfonyConfig()['domains'] ?? [];
}

/**
 * Get Symfony Local Server proxy domain for current request.
 *
 * Since there is domain information on CLI, use the document root to identify the domain.
 */
function getCurrentSymfonyDomain(): ?string
{
    /** @var string|null */
    static $symfonyCurrentDomain;
    if (isset($symfonyCurrentDomain)) {
        return $symfonyCurrentDomain;
    }

    $tld = getSymfonyProxyTld();
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
    if (! is_string($documentRoot)) {
        return $symfonyCurrentDomain = null;
    }

    foreach (getSymfonyProxyDomains() as $domain => $path) {
        if (str_starts_with($documentRoot, $path)) {
            return $symfonyCurrentDomain = sprintf('%s.%s', $domain, $tld);
        }
    }

    return $symfonyCurrentDomain = null;
}

/**
 * Identify if running in Symfony Local Server.
 */
function isSymfonyLocalServer(): bool
{
    /** @var bool|null */
    static $isSymfonyLocalServer;
    if (isset($isSymfonyLocalServer)) {
        return $isSymfonyLocalServer;
    }

    // Non CLI requests
    if (! in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) && isset($_SERVER['SERVER_SOFTWARE']) && is_string($_SERVER['SERVER_SOFTWARE'])) {
        return $isSymfonyLocalServer = explode('/', $_SERVER['SERVER_SOFTWARE'])[0] === 'symfony-cli';
    }

    return $isSymfonyLocalServer = getCurrentSymfonyDomain() !== null;
}

/**
 * Send through proxy if using Symfony Local Server.
 *
 * @param bool|null                $override Whether to send the request through the proxy. Default null.
 * @param string                   $uri      URL of the request.
 * @param array<string, int|string> $check    Associative array result of parsing the request URL with `parse_url()`.
 * @param array<string, int|string> $home     Associative array result of parsing the site URL with `parse_url()`.
 */
function shouldSendThroughProxy($override, $uri, $check, $home): ?bool
{
    return isset($check['host']) && isset($home['host']) && $check['host'] === $home['host'] ? true : $override;
}

/**
 * Provides SSL certificate for requests to the local server.
 *
 * @param bool|string $verify Boolean to control whether to verify the SSL connection or path to an SSL certificate.
 * @param string $url The request URL.
 */
function verifySsl($verify, $url): bool|string
{
    if (parse_url($url, PHP_URL_HOST) !== parse_url(home_url(), PHP_URL_HOST)) {
        return $verify;
    }

    return sprintf('%s/.symfony5/certs/rootCA.pem', getHomeDir());
}

/**
 * Redirect wp-admin to wp-admin/index.php since Symfony Local Server doesn't support index.php in subdirectories (yet).
 *
 * @see https://github.com/symfony-cli/symfony-cli/issues/237
 *
 * @param string|false|null $redirect_url  The redirect URL.
 * @param string $requested_url The requested URL.
 * @return string|false|null
 */
function redirectWpAdmin($redirect_url, $requested_url)
{
    $adminUrlPath = parse_url(admin_url(), PHP_URL_PATH);
    $requestedUrlPath = parse_url($requested_url, PHP_URL_PATH);
    $requestUrlBasename = pathinfo((string) $requestedUrlPath, PATHINFO_BASENAME);
    if ($requestUrlBasename === 'index.php' && is_string($requestedUrlPath)) {
        $requestedUrlPath = rtrim($requestedUrlPath, $requestUrlBasename);
    }
    if ($requestedUrlPath === $adminUrlPath) {
        return admin_url('index.php');
    }

    return $redirect_url;
}

/**
 * Change redirect status so redirects to admin don't get cached.
 *
 * @param int    $status   The HTTP response status code to use.
 * @param string $location The path or URL to redirect to.
 */
function redirectWpAdminStatus($status, $location): int
{
    // May happen when WP_INSTALLING
    if (! function_exists('admin_url')) {
        return $status;
    }

    return admin_url('index.php') === $location ? 302 : $status;
}

/**
 * Adds index.php to the admin URL if no path is specified.
 *
 * @param string      $url     The complete admin area URL including scheme and path.
 * @param string      $path    Path relative to the admin area URL. Blank string if no path is specified.
 * @param int|null    $blog_id Site ID, or null for the current site.
 * @param string|null $scheme  The scheme to use. Accepts 'http', 'https',
 *                             'admin', or null. Default 'admin', which obeys force_ssl_admin() and is_ssl().
 */
function rewriteAdminUrl($url, $path, $blog_id, $scheme): string
{
    $pathParts = parse_url($path);
    // It already has a path
    if (! empty($pathParts['path'])) {
        return $url;
    }

    return empty($path) ? sprintf('%s/index.php', rtrim($url, '/')) : str_replace($path, sprintf('index.php%s', $path), $url);
}

if (isSymfonyLocalServer()) {
    // Define proxy settings if running in Symfony Local Server
    defined('WP_PROXY_HOST') || define('WP_PROXY_HOST', 'http://127.0.0.1');
    defined('WP_PROXY_PORT') || define('WP_PROXY_PORT', (string) getSymfonyProxyPort());

    Hook::addFilter('pre_http_send_through_proxy', __NAMESPACE__ . '\\shouldSendThroughProxy', 10, 4);
    Hook::addFilter('https_ssl_verify', __NAMESPACE__ . '\\verifySsl', 10, 2);
    Hook::addFilter('redirect_canonical', __NAMESPACE__ . '\\redirectWpAdmin', PHP_INT_MAX, 2);
    Hook::addFilter('wp_redirect_status', __NAMESPACE__ . '\\redirectWpAdminStatus', 10, 2);
    Hook::addFilter('admin_url', __NAMESPACE__ . '\\rewriteAdminUrl', PHP_INT_MAX, 4);
}
