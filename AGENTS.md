# SME Management Pilot — instructions for AI agents

These instructions apply to every AI agent working in this repository. `CLAUDE.md` imports this file and adds Claude Code's orchestration policy.

## Project

- Business management application for a fictional 12-person maintenance and facilities company in Barcelona. Modular monolith.
- Laravel 13, Filament 5 (admin panel at `/admin`), PHP 8.3, Composer 2.
- SQLite for local development. Keep everything PostgreSQL-compatible: use Laravel's schema builder and query builder, no SQLite-specific SQL.
- Timezones: timestamps are stored in UTC (`config/app.php`); the Filament panel displays and accepts date-times in `Europe/Madrid` (`FilamentTimezone` in `AppServiceProvider`).
- Tests: PHPUnit (Laravel default). Code style: Laravel Pint.
- Implemented: Client Management (clients, contacts and addresses, with role-based access for `admin`, `office_staff` and `technician`; roles are described in `README.md`). Still pending: quotes, work orders, scheduling, hours and materials, PDF reports, dashboard. Implement pending modules only when the user asks.
- Setup and run instructions: `README.md`.

## Commands

```sh
php -v                       # check PHP is available
composer -V                  # check Composer is available
php artisan test             # run tests
./vendor/bin/pint --test     # check code style (omit --test to fix)
php artisan serve            # run locally, then open /admin
```

If PHP or Composer is unavailable, tell the user. Do not install them.

## Dependencies

- Do not install, update or remove dependencies (Composer or npm packages, PHP extensions, system tools) without explicit user approval.
- Laravel Boost is **not** installed. Do not install it or run `boost:install`. The default Laravel Boost bootstrap instructions were removed from this file and from `CLAUDE.md` on purpose.

## Working rules

- Follow the existing Laravel and Filament conventions of this project.
- Make small, focused changes. No unrelated refactoring or cleanup.
- Do not claim that tests or commands ran unless they actually ran.

## Protected file: `php_artisan_password.txt`

The project owner created this file in the project root on purpose, and it is listed in `.gitignore`. It is expected. Do not report it as unknown or suspicious. Do not delete, rename or modify it, and do not ask the user to remove it.

- **Never read, open, print or copy its contents**, and never include them, in whole or in part, in prompts, agent context files, logs, outputs or generated artifacts.
- `.gitignore` only stops Git from tracking it. Repository scanners and context builders (for example AI-Dev-Harness `scan-repository.ps1`) still list it, so exclude it explicitly whenever files are selected for context.
- Exclude it from content searches over the project (for example `--glob '!php_artisan_password.txt'`).
- Never propose or write changes to it.
