# Upgrade: Laravel 8 / PHP 8.1 to Laravel 13 / PHP 8.4

## Why

On 21 October 2026 PHP shipped fixes for two critical remote code execution
vulnerabilities (CVE-2026-109618, CVE-2026-109619). Fixed releases: 8.5.12,
8.4.27, 8.3.36 and 8.2.35. PHP 8.1 (what the server runs) is end of life and
has no patched binary.

Laravel 8 is not supported on any patched PHP branch, so the application was
moved to Laravel 13, which supports PHP 8.3 - 8.5 and receives security fixes
until March 2028. The target runtime is PHP 8.4.27 (security fixes until
December 2028).

## What changed in the code

| Area | Before | After |
| --- | --- | --- |
| Framework | Laravel 8.83 | Laravel 13 (new `bootstrap/app.php` skeleton, no `Kernel` classes) |
| Auth scaffolding | Jetstream 1 / Fortify 1.16 | Jetstream 5 / Fortify 1.x, views re-published; `x-jet-*` components are now `x-*` |
| Livewire | 2.x (`App\Http\Livewire`) | 3.x (`App\Livewire`); filter inputs use `wire:model.live` |
| API auth | Passport 10 (`Passport::routes()`, `client` middleware) | Passport 13, `client` alias maps to `EnsureClientIsResourceOwner` |
| Session auth | Sanctum 2 | Sanctum 4 |
| Assets | Laravel Mix 5, Tailwind 1, Alpine 2 from CDN | Vite 7, Tailwind 3.4, Alpine bundled by Livewire; built files committed in `public/build` |
| Scheduler | `app/Console/Kernel.php` | `routes/console.php` |
| Removed | `fideloper/proxy`, `fruitcake/laravel-cors`, `facade/ignition`, `public/phpinfo.php`, `public/test.php` | Built-in replacements / deleted |

Behaviour differences worth knowing:

- Validation failures on `api/*` routes that are not the `transaction_id` /
  `customer_code` SFA responses now return a normal 422 JSON error. Under
  Laravel 8 the custom handler returned `null` for those, which produced a 500.
- Sessions are serialized as JSON. Every signed-in user must log in again after
  the deploy.
- Cache keys and the session cookie name follow the Laravel 13 defaults, so
  file cache entries are rebuilt on first use.
- Laravel 13's CSRF middleware also checks the request origin
  (`Sec-Fetch-Site`). Normal browser traffic is unaffected.

## Server upgrade (Windows, IIS FastCGI + scheduler)

Do this in a maintenance window. SFA pushes orders through this app, so the
OAuth client must keep working at the end.

1. Back up the SQL Server database and the application folder
   (`C:\Solutech\SAT`), including `storage\oauth-private.key` and
   `storage\oauth-public.key`.
2. Install PHP 8.4.27:
   - Download `php-8.4.27-nts-Win32-vs17-x64.zip` from
     https://downloads.php.net/~windows/releases/ and extract it to
     `C:\Solutech\php\php84`. Install the Visual C++ 2015-2022 x64
     redistributable if it is not already present.
   - Download Microsoft Drivers 5.13 for PHP for SQL Server and copy
     `php_sqlsrv_84_nts.dll` and `php_pdo_sqlsrv_84_nts.dll` into
     `C:\Solutech\php\php84\ext`. ODBC Driver 17 or 18 for SQL Server must be
     installed.
   - Create `php.ini` from `php.ini-production` and enable at least:
     `curl`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_mysql`,
     `pdo_sqlsrv`, `sodium`, `sqlsrv`, `zip`, `com_dotnet`, `opcache`. Copy over any
     custom values from the old `php81\php.ini` (memory_limit,
     max_execution_time, upload sizes, date.timezone, extension_dir).
   - Check: `C:\Solutech\php\php84\php.exe -m` lists `pdo_sqlsrv`.
3. Point IIS at the new binary. `public/web.config` in this release already
   references `C:\Solutech\php\php84\php-cgi.exe`. If the FastCGI application
   is also registered at server level (IIS Manager > FastCGI Settings), add the
   php84 entry there too.
4. Deploy the code and dependencies:

   ```
   git fetch && git checkout upgrade/laravel-13   (or the merged main)
   C:\Solutech\php\php84\php.exe composer.phar install --no-dev --optimize-autoloader
   ```

   The compiled front-end is committed in `public/build`, so Node is not
   required on the server.
5. Update `.env` (names changed in Laravel 11+; the old names are ignored):

   ```
   CACHE_STORE=file            (was CACHE_DRIVER)
   BROADCAST_CONNECTION=log    (was BROADCAST_DRIVER)
   FILESYSTEM_DISK=local       (was FILESYSTEM_DRIVER)
   APP_TIMEZONE=Africa/Nairobi (only if the old config/app.php timezone was changed)
   ```

   Keep `DB_CONNECTION=sqlsrv`, `SESSION_DRIVER=database` and the
   `SAGE_*` / `SFA_*` values as they are.
6. Apply the schema changes. Do **not** run `php artisan migrate` on production;
   instead run `database/sql/production-upgrade-laravel-13.sql` against the
   application database in SSMS or with sqlcmd:

   ```
   sqlcmd -S <server> -d <database> -E -b -i database/sql/production-upgrade-laravel-13.sql
   ```

   It is idempotent and transactional. It creates `oauth_device_codes`, adds
   `users.two_factor_confirmed_at`, converts `oauth_clients` to the Passport 13
   layout (`owner_*`, `redirect_uris`, `grant_types`) and records the
   migrations so Laravel considers them run. Existing clients, tokens and the
   personal access client used by the dashboard are preserved.
7. Hash the OAuth client secrets. Passport 13 only accepts hashed secrets and
   Passport 10 stored them in plain text:

   ```
   php artisan passport:hash
   ```

   The SFA system keeps using the same client id and secret; only the stored
   copy changes. This step is irreversible, which is why step 1 matters.
8. Clear caches and recreate the storage link:

   ```
   php artisan optimize:clear
   php artisan storage:link
   ```

   Do not run `config:cache`: the application reads `env()` directly in
   controllers and commands, which returns `null` once config is cached.
9. Restart the scheduler. `sync_pev_sfa.bat` now runs
   `C:\Solutech\php\php84\php.exe artisan schedule:work`; stop the old window
   or service and start it again.
10. Recycle the IIS application pool.

### Verify

- `GET /up` returns 200.
- Log in, open Dashboard, Orders, Customers, Products, Track Order Status and
  Manage Users; filters on Orders should update as you type.
- From SFA (or curl): `POST /oauth/token` with `grant_type=client_credentials`
  returns a token, and `GET /api/pev/customers` with that bearer token returns
  data.
- Scheduler log shows `command:pushorderstatustosfa` running every minute.
- Once everything works, remove `C:\Solutech\php\php81`.

## Rollback

Restore the database backup (needed because of `passport:hash` and the
`oauth_clients` migration), check out the previous commit, point IIS and the
scheduler back at php81 and run the old `composer install`.

## Local development notes

- Minimum PHP is 8.3. On machines with Windows Smart App Control enabled an
  unsigned `php.exe` from windows.php.net is blocked ("An Application Control
  policy has blocked this file"). Options: run PHP and Composer inside WSL,
  or install a signed distribution such as Laravel Herd.
- Smart App Control also blocks the native Rollup/rolldown bindings used by
  Vite. `package.json` pins Vite 7 and overrides `rollup` with
  `@rollup/wasm-node`, which runs as WebAssembly and needs no native binary.
- Build assets with `npm install && npm run build` and commit `public/build`.
- Tests run against an in-memory SQLite database: `php artisan test`. When using
  WSL, copy the project to the Linux filesystem first (for example
  `rsync -a --exclude vendor --exclude node_modules . ~/ci/app`): running
  PHPUnit directly on `/mnt/d` takes minutes per test.
