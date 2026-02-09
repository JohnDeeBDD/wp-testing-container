# wp-testing-container

WordPress plugin testing container — run Codeception "wpunit" tests against a
real WordPress install backed by a disposable MariaDB database.

Designed so AI agents (Codex, Claude Code, etc.) can clone the repo, run one
command, and get a green/red test result with zero interactive prompts.

## Prerequisites

- Docker & Docker Compose v2+
- (Optional) PHP 8.2+ & Composer on the host — or let the container handle it

## Quick start

```bash
# 1. Bring up containers + install WP + activate plugin + composer install
./bin/test-setup

# 2. Run the Codeception wpunit suite
./bin/test
```

That's it. Both scripts are idempotent and non-interactive.

## Repository layout

```
bin/
  test-setup          # idempotent: containers → DB → WP install → plugin activate → composer
  test                # runs codecept inside the wp container
docker/
  php/Dockerfile      # PHP 8.2 + WP-CLI + Composer
plugin/
  my-plugin.php       # sample plugin under test
tests/
  wpunit/
    _bootstrap.php    # loads WordPress before tests
    PluginActivationTest.php
  wpunit.suite.yml    # Codeception suite config
codeception.yml       # top-level Codeception config
composer.json         # dev deps: codeception ^5
docker-compose.yml    # MariaDB (tmpfs) + WP-CLI container
```

## How it works

1. **docker-compose.yml** defines two services:
   - `db` — MariaDB 10.11, database stored in `tmpfs` (RAM-only, fast, disposable)
   - `wp` — PHP 8.2 CLI image with WP-CLI and Composer

2. **bin/test-setup** (runs on the host, shells into `wp`):
   - Starts containers, waits for DB health check
   - Drops & recreates the `wordpress_test` database
   - Downloads WP core (skipped if already present)
   - Generates `wp-config.php`
   - Runs `wp core install` with dummy credentials
   - Symlinks `plugin/` into `wp-content/plugins/` and activates it
   - Runs `composer install`

3. **bin/test** runs `vendor/bin/codecept run wpunit` inside the container.

4. **tests/wpunit/_bootstrap.php** loads `/var/www/html/wp-load.php` so every
   test has the full WordPress API available.

## Customizing for your plugin

1. Replace `plugin/my-plugin.php` with your plugin directory/files.
2. Update `PLUGIN_SLUG` and `PLUGIN_DIR` in `docker-compose.yml`.
3. Add your tests under `tests/wpunit/`.
4. If you need additional PHP extensions, add them to `docker/php/Dockerfile`.

## Environment variables

All configuration is in `docker-compose.yml` environment section. Defaults:

| Variable          | Default              |
|-------------------|----------------------|
| `DB_HOST`         | `db`                 |
| `DB_NAME`         | `wordpress_test`     |
| `DB_USER`         | `root`               |
| `DB_PASS`         | `root`               |
| `WP_URL`          | `http://localhost`   |
| `WP_TITLE`        | `Test Site`          |
| `WP_ADMIN_USER`   | `admin`              |
| `WP_ADMIN_PASS`   | `admin`              |
| `WP_ADMIN_EMAIL`  | `admin@example.com`  |
| `PLUGIN_SLUG`     | `my-plugin`          |
| `PLUGIN_DIR`      | `/app/plugin`        |

## Passing extra flags to Codeception

```bash
./bin/test -- --filter Greet    # run only tests matching "Greet"
./bin/test -- --debug           # verbose debug output
```

## Tearing down

```bash
docker compose down -v   # stops containers and removes the volume
```
