# Changelog

## 5.0.0 - 2026-11-25

### Breaking change

Rename the public PHP namespace from `Triibo\Mautic` to `CostaMateus\Mautic`
throughout the package: PSR-4 autoloading, Laravel discovery metadata, provider
bindings, routes, controllers, facade, factory, model, exception and tests.
No compatibility aliases are provided for the old namespace.

Consumers must update imports, manually registered providers, extended class
names and other fully qualified references before booting the upgraded app.
Drain jobs serialized with old class names before upgrading or migrate them
explicitly, clear application caches and restart long-running workers.
See the README upgrade instructions.

Validation: all 14 regression checks pass on the seven supported PHP/Laravel
combinations and experimental PHP 8.6/Laravel 13. Installation as a dependency
also passes Laravel package discovery, facade alias, config and route checks
under the new namespace. PHP 8.6/Laravel 12 retains the v4 upstream limitation.

The Composer package name, dependency requirements, facade alias `Mautic`,
container service names, routes, configuration keys, database table and migration
remain unchanged. Existing credentials and token records are retained. No schema
migration is required. Existing license and contributor credits are preserved.

## 4.0.0 - 2026-11-25

### Compatibility investigation

The published baseline is Packagist `v3.0.5`, commit
`7f46525283fb48ef7047698a4eb1e8d2dccbacd1`, released on 2025-10-10.
The local checkout initially lagged three commits behind that baseline and was
fast-forwarded before changes. Existing local `v3.0.4` and `v3.0.5` tags differ
from the public tags; they were not rewritten. Use the public commit above when
comparing this release with the published package.

Findings and resulting changes:

| Finding in the published baseline | Result |
| --- | --- |
| `laravel/framework ^12.0` prevents installation on Laravel 13. | Accept `^12.0 || ^13.0`; Laravel 13 itself requires PHP 8.3+. |
| `php ^8.2` already permits newer PHP 8 releases. | Keep this requirement; changing the PHP minimum is unnecessary. Composer acceptance alone is not runtime validation. |
| `graham-campbell/manager ^5.1` already permits 5.3, which adds Laravel 13 support. | Raise the minimum to `^5.3` to explicitly require that support. |
| `guzzlehttp/guzzle ^7.9` already permits the latest compatible 7.x version. | Raise the minimum to `^7.17`. Guzzle 8.6 requires PSR-7 `^3.6`, incompatible with Mautic SDK 4's PSR-7 `^2.4`; do not enable Guzzle 8 yet. |
| `psr/log ~2.0` unnecessarily excludes PSR Log 3. The package never calls its interfaces directly. | Remove the direct requirement; Laravel/Monolog and the SDK negotiate compatible logging dependencies. This was not, by itself, a Laravel 12 installation failure. |
| The SDK requirement excludes stable Mautic API Library 4.0.0. | Upgrade to `^4.0` and supply Guzzle explicitly as its PSR-18 client, avoiding reliance on an enabled discovery plugin. No blanket PHP incompatibility of SDK 3 is claimed. |
| `Mautic::request()` imports `Mautic\MauticConsumer`, which does not exist. | Import the package's actual Eloquent model; preserve the upstream refresh-token fix. |
| Routes and migration assume global `Route` and `Schema` aliases. | Import the Laravel facades explicitly and use a controller class route. These failures also reproduce on Laravel 12 without legacy aliases. |
| The callback controller inherits the host application's controller. | Inherit Laravel's routing controller so the package loads independently of the host's controller layout. |
| Installation instructions assume the old `config/app.php` providers/aliases structure; discovery metadata and default configuration merging are missing. | Add Laravel package discovery, merge defaults and document `bootstrap/providers.php` for disabled discovery. |
| BasicAuth requests still query OAuth tokens and may initiate OAuth. | Send BasicAuth requests directly, without an OAuth table or token. Select `default => basic` in the published configuration. |
| A token expiring at the current timestamp is treated as valid. | Expire at `now >= expires`. |
| Token selection orders only by `created_at`, which can tie for tokens created in the same second. | Select the highest auto-increment ID so subsequent requests reuse the newest token. A regression check reproduced the unnecessary second refresh before the fix. |
| OAuth initialization starts/renames an already active native PHP session. | Reuse an active session and start one only when necessary. |
| An unsuccessful OAuth flow can leave a null consumer dereference. | Throw a package exception if the flow returns without a consumer. SDK redirects/exceptions retain their own behavior. |
| CI only installs dependencies on PHP 8.2; no tests run. | Add a standalone regression check and a PHP/Laravel matrix, including non-blocking experimental PHP 8.6 jobs. |

### Executed validation

All tests use actual Laravel services, an in-memory SQLite database and HTTP on
loopback. No real customer database, Mautic account or remote API was used.
Warnings and deprecations are converted into exceptions (`E_ALL`).

| Framework | PHP runtimes | Result |
| --- | --- | --- |
| Laravel 12.74.0 | 8.2.36, 8.3.37, 8.4.16, 8.5.13 | 14 checks pass on each runtime. |
| Laravel 13.44.1 | 8.3.37, 8.4.16, 8.5.13 | 14 checks pass on each runtime. |
| Laravel 13.44.1 | 8.6.0 | Experimental: 14 checks pass. Laravel's published PHP support table currently ends at 8.5. |
| Laravel 12.74.0 | 8.6.0 | Experimental: strict startup fails in Laravel's container because `spl_object_hash()` is deprecated in PHP 8.6. This is upstream code, not suppressed or patched here. |
| Laravel 13 | PHP 8.2 | Unsupported by Laravel; deliberately excluded from the matrix. |

Two dependency graphs were resolved for the respective minimum PHP versions
(8.2 for Laravel 12, 8.3 for Laravel 13) and executed across the runtimes above.
An additional Laravel 13 graph resolved on PHP 8.4, including Symfony 8.1
components, was checked on PHP 8.4, 8.5 and 8.6. This is not an exhaustive test of
all historical dependency versions accepted by the constraints.

The package was also installed as a dependency in a disposable consumer through
a Composer path repository. Laravel's generated package manifest discovered the
provider and facade alias; configured provider boot registered the manager,
default configuration and callback route without manual package registration.

The 14 checks cover provider/config boot, migration, controller loading, callback
dispatch and route compilation, bearer authentication, token renewal and
persistence, failed refresh preservation, HTTP 404 return behavior, expiry
boundary, BasicAuth without an OAuth table, missing-token exceptions, the SDK's
OAuth callback with an active session, fallback after a rejected refresh and
deterministic token selection when timestamps tie.

`composer validate --strict` passes. `composer audit` reported no known security
advisories in the three resolved dependency graphs on the investigation date.
This does not establish that the package or its dependencies have no vulnerabilities.

### Upgrade notes and remaining limits

This is a major release because it moves the required Mautic SDK from 3.x to
4.x, which adopts PSR-18 transport and changes upstream authentication internals.
Applications directly using the SDK must review their own SDK calls as well.
The package namespace, facade, token table and `request()` signature are retained;
successful JSON responses and HTTP client-error status-code returns are retained.

For an existing application, remove any duplicate manual provider registration
when using discovery. Keep existing credentials and token records. Do not blindly
overwrite a published configuration or publish/run a duplicate token migration.
BasicAuth selects the `basic` connection through `mautic.default`; the legacy
top-level `MAUTIC_AUTH_VERSION` does not switch that connection.

No live Mautic-server version matrix or real browser authorization round trip was
executed. The OAuth callback test exchanges a code through the actual SDK against
the local HTTP server; production cookies, TLS, proxy configuration and server
authorization rules still require integration validation. Native PHP sessions
remain part of the existing OAuth design. The library still uses one global
consumer history and hard-codes `main` for OAuth setup/refresh, so arbitrary
independent OAuth connections are not established by these tests. Existing GET
parameter handling, request timeouts, refresh concurrency and token-history
retention were not redesigned in this compatibility update.

### Primary sources

Verified on 2026-11-25:

- [Published package and release](https://packagist.org/packages/costamateus/laravel-mautic-api)
- [Laravel releases and supported PHP versions](https://laravel.com/docs/13.x/releases)
- [Laravel 13 upgrade guide](https://laravel.com/docs/13.x/upgrade)
- [Laravel package discovery](https://laravel.com/docs/13.x/packages)
- [PHP supported versions](https://www.php.net/supported-versions.php)
- [PHP 8.6 deprecations](https://www.php.net/manual/en/migration86.deprecated.php)
- [Mautic SDK 4 requirements](https://github.com/mautic/api-library/blob/4.0.0/composer.json)
- [Guzzle 8.6 requirements](https://github.com/guzzle/guzzle/blob/8.6.0/composer.json)
- [Manager 5.3 requirements](https://github.com/GrahamCampbell/Laravel-Manager/blob/v5.3.0/composer.json)

The release version is derived from the Git tag; no hard-coded Composer
`version` field or library lock file is distributed.
