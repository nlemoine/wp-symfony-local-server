<?php

declare(strict_types=1);

namespace n5s\WpSymfonyLocalServer;

use function WeCodeMore\earlyAddFilter;

/**
 * Get Symfony Local Server configuration.
 *
 * @return array|null
 */
function getSymfonyConfig(): array
{
    static $symfonyConfig;
    if (isset($symfonyConfig)) {
        return $symfonyConfig;
    }

    $symfonyconfigPath = sprintf('%s/.symfony5/proxy.json', $_SERVER['HOME'] ?? '');
    if (! is_readable($symfonyconfigPath)) {
        return $symfonyConfig = [];
    }
    return $symfonyConfig = json_decode(file_get_contents($symfonyconfigPath), true);
}

/**
 * Get Symfony Local Server proxy port.
 */
function getSymfonyProxyPort(): string
{
    return (string) getSymfonyConfig()['port'] ?? '7080';
}

/**
 * Get Symfony Local Server proxy port.
 */
function getSymfonyProxyTld(): string
{
    return getSymfonyConfig()['tld'] ?? 'wip';
}

/**
 * Get Symfony Local Server proxy domains.
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
    static $symfonyCurrentDomain;
    if (isset($symfonyCurrentDomain)) {
        return $symfonyCurrentDomain;
    }

    $tld = getSymfonyProxyTld();
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
    if ($documentRoot === null) {
        return $symfonyCurrentDomain = null;
    }

    foreach (getSymfonyProxyDomains() as $domain => $path) {
        if (str_starts_with($documentRoot, $path)) {
            return $symfonyCurrentDomain = sprintf('%s.%s', $domain, $tld);
        }
    }

    return $symfonyCurrentDomain = null;
}

// Automatically set WP_HOME to the current Symfony Local Server domain
getCurrentSymfonyDomain() && define('WP_HOME', sprintf('https://%s', getCurrentSymfonyDomain()));

// Define proxy settings if running in Symfony Local Server
defined('WP_PROXY_HOST') || define('WP_PROXY_HOST', 'http://127.0.0.1');
defined('WP_PROXY_PORT') || define('WP_PROXY_PORT', getSymfonyProxyPort());

/**
 * Identify if running in Symfony Local Server.
 */
function isSymfonyLocalServer(): bool
{
    static $isSymfonyLocalServer;
    if (isset($isSymfonyLocalServer)) {
        return $isSymfonyLocalServer;
    }

    // Non CLI requests
    if (! in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
        return $isSymfonyLocalServer = explode('/', $_SERVER['SERVER_SOFTWARE'] ?? '')[0] === 'symfony-cli';
    }
    return $isSymfonyLocalServer = getCurrentSymfonyDomain() !== '';
}

/**
 * Send through proxy if using Symfony Local Server.
 *
 * @param string    $uri      URL of the request.
 * @param array     $check    Associative array result of parsing the request URL with `parse_url()`.
 * @param array     $home     Associative array result of parsing the site URL with `parse_url()`.
 */
function shouldSendThroughProxy($null, $uri, $check, $home): ?bool
{
    if ($null !== null) {
        return $null;
    }

    return isset($check['host']) && isset($home['host']) && $check['host'] === $home['host'] ? true : $null;
}

/**
 * Removes SSL verification for requests to the local server.
 * @param bool|string $verify Boolean to control whether to verify the SSL connection
 *                                or path to an SSL certificate.
 * @param string $url The request URL.
 */
function verifySsl($verify, $url): bool|string
{
    if ($verify === false || is_string($verify)) {
        return $verify;
    }
    return parse_url($url, PHP_URL_HOST) === parse_url(get_option('siteurl'), PHP_URL_HOST) ? false : $verify;
}

/**
 * Redirect wp-admin to wp-admin/index.php since Symfony Local Server doesn't support index.php in subdirectories (yet).
 *
 * @see https://github.com/symfony-cli/symfony-cli/issues/237
 *
 * @param string $redirect_url  The redirect URL.
 * @param string $requested_url The requested URL.
 * @return string|false|null
 */
function redirectWpAdmin($redirect_url, $requested_url)
{
    $adminUrlPath = parse_url(admin_url(), PHP_URL_PATH);
    $requestedUrlPath = parse_url($requested_url, PHP_URL_PATH);
    $requestUrlBasename = pathinfo($requestedUrlPath, PATHINFO_BASENAME);
    if ($requestUrlBasename === 'index.php') {
        $requestedUrlPath = rtrim($requestedUrlPath, $requestUrlBasename);
    }
    if ($requestedUrlPath === $adminUrlPath) {
        return admin_url('index.php');
    }
    return $redirect_url;
}

/**
 * Chnage redirect status so redirects to admin don't get cached.
 *
 * @param int    $status   The HTTP response status code to use.
 * @param string $location The path or URL to redirect to.
 */
function redirectWpAdminStatus($status, $location): int
{
    return admin_url('index.php') === $location ? 302 : $status;
}

if (isSymfonyLocalServer()) {
    earlyAddFilter('pre_http_send_through_proxy', __NAMESPACE__ . '\\shouldSendThroughProxy', 10, 4);
    earlyAddFilter('https_ssl_verify', __NAMESPACE__ . '\\verifySsl', 10, 2);
    earlyAddFilter('redirect_canonical', __NAMESPACE__ . '\\redirectWpAdmin', 10, 2);
    earlyAddFilter('wp_redirect_status', __NAMESPACE__ . '\\redirectWpAdminStatus', 10, 2);
}
