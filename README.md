# SME Management Pilot

Business management application for a fictional 12-person maintenance and facilities company in Barcelona.

Built as a modular monolith with Laravel and the Filament admin panel.

**Current status:** the Client Management module (clients, contacts and addresses, with role-based access) is implemented. The other business modules (quotes, work orders, scheduling, hours and materials, PDF reports, dashboard) are still pending.

## Stack

- PHP 8.3
- Laravel 13
- Filament 5 (panel at `/admin`)
- SQLite for local development; PostgreSQL for production
- PHPUnit (Laravel default) and Laravel Pint

## Requirements

- PHP 8.3 with these extensions: `intl`, `zip`, `pdo_sqlite`, `mbstring`, `fileinfo`, `curl`, `openssl`, `xml`
- Composer 2

## Setup

```bash
composer install
cp .env.example .env          # on Windows PowerShell: Copy-Item .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan make:filament-user
```

## Run

```bash
php artisan serve
```

Open <http://localhost:8000/admin> and log in with the user created by `make:filament-user`.

## Roles

Panel access requires a role (`users.role`): `admin`, `office_staff` or `technician`. Users without a role are denied access. `make:filament-user` does not set a role; assign one with:

```bash
php artisan tinker --execute="App\Models\User::where('email', 'you@example.com')->update(['role' => 'admin']);"
```

| Role | Clients, contacts and addresses |
|---|---|
| `admin` | Full access |
| `office_staff` | Full access |
| `technician` | Read only |

Timestamps are stored in UTC; the panel shows them in `Europe/Madrid`.

## Test and check

```bash
php artisan test              # PHPUnit
./vendor/bin/pint --test      # code style check (omit --test to fix)
```

## Switching to PostgreSQL

The `pgsql` connection is already defined in `config/database.php`. In `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sme_management
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Then run `php artisan migrate`. The `pdo_pgsql` PHP extension must be enabled. Write migrations with Laravel's schema builder and avoid SQLite-specific SQL so both databases keep working.

## Before production

- Make sure every real user has the intended role.
- Set `APP_ENV=production`, `APP_DEBUG=false` and a real `APP_URL`.
