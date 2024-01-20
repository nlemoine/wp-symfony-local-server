# WP Symfony Local Server

A set a hooks to fix running WordPress on [Symfony Local Server](https://symfony.com/doc/current/setup/symfony_server.html) with local domain names.

Currenlty solve:
- Self requests (`wp_remote_get('https://domain.wip/page/')`): send them through Symfony local proxy so local TLD is resolved and set certificate to avoid SSL errors
- Admin redirects: https://domain.wip/wp-admin/ -> https://domain.wip/wp-admin/index.php
- Automatic WP_HOME constant set according to local domain

```bash
composer require n5s/wp-symfony-local-server --dev
```
