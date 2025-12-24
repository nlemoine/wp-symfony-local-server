# WP Symfony Local Server

Fixes WordPress compatibility issues when running on [Symfony Local Server](https://symfony.com/doc/current/setup/symfony_server.html) with local domains (`.wip`).

## Installation

```bash
composer require n5s/wp-symfony-local-server --dev
```

That's it. The library auto-detects Symfony Local Server and registers the necessary hooks.

## What it fixes

| Problem | Solution |
|---------|----------|
| Self-requests fail (SSL errors, unresolved `.wip` TLD) | Routes them through Symfony's proxy with proper certificates |
| `/wp-admin/` causes redirect loops | Rewrites to `/wp-admin/index.php` ([why?](https://github.com/symfony-cli/symfony-cli/issues/237)) |
| Redirect caching issues | Uses HTTP 302 instead of 301 for admin redirects |

## How it works

The library only activates when it detects Symfony Local Server (via `SERVER_SOFTWARE` header or `~/.symfony5/proxy.json` config). It then:

1. Sets `WP_PROXY_HOST` and `WP_PROXY_PORT` to route internal requests through Symfony's proxy
2. Provides Symfony's root CA certificate for SSL verification
3. Hooks into `admin_url` and `redirect_canonical` to fix admin URL handling

## Requirements

- PHP 8.2+
- Symfony CLI with local proxy configured
