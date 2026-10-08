# CP HR Portal

Private internal HR administration application built with Laravel 13, PHP 8.3+, Blade, Tailwind CSS, Alpine.js, and a MySQL-compatible database.

Phase 0 provides the technical foundation only. Employee records, attendance, leave, contracts, documents, policies, holidays, reporting, and employee self-service are intentionally not implemented.

## Local development

### Requirements

- PHP 8.3 or newer with Laravel's required extensions and `pdo_mysql`
- Composer 2
- MySQL 8.4 or another compatible MySQL server
- Node.js and npm for compiling frontend assets

### Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
```

Create a local database named `hr_portal`, then review these local `.env` values:

```dotenv
APP_NAME="CP HR Portal"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hr_portal
DB_USERNAME=root
DB_PASSWORD=
```

Never commit `.env` or credentials.

Run migrations and optionally install the local-only fictitious development accounts:

```bash
php artisan migrate
php artisan db:seed
```

The local development seeder creates the following clearly fictitious accounts with password `LocalPassword123!`:

- `super.admin@example.test`
- `hr.admin@example.test`
- `employee@example.test`

The development user seeder refuses to run outside the `local` environment. Do not use these credentials in production.

Start the application and frontend compiler:

```bash
php artisan serve
npm run dev
```

Or build static production assets:

```bash
npm run build
```

Node.js is a build dependency only. The production application runs with PHP and does not require a continuously running Node.js process.

### Tests and formatting

Tests use an in-memory SQLite database for speed and isolation. MySQL migrations must also be verified locally before deployment.

```bash
php artisan test --compact
vendor/bin/pint --format agent
```

## Authentication

Laravel Fortify provides login, logout, password reset, and password confirmation. Login attempts are rate limited. Public registration is intentionally disabled; there is no public account-creation route.

Password reset delivery uses the configured Laravel mailer. Local development logs mail by default; tests use Laravel's notification fake.

## Roles and authorization

The application defines these stable role slugs:

- `super-admin` — global authorization override
- `hr-admin` — access to HR administration routes
- `employee` — no administration access; the future employee portal is not implemented

Roles use Laravel-native Gates and middleware. The role relationship supports additional roles such as `manager` without changing the users table. Navigation visibility is not treated as an authorization control; `/admin` is protected server-side.

## First production Super Admin

After configuring production environment variables and running migrations, create the first administrator interactively:

```bash
php artisan app:create-super-admin admin@example.com --name="Administrator"
```

The command securely prompts for the password without requiring it on the command line, validates password strength, rejects duplicate email addresses, and assigns the `super-admin` role. Do not put a production password in shell history, deployment scripts, source control, or environment examples.

## Private document storage foundation

Future confidential HR documents must use the `hr-private` filesystem disk, rooted at `storage/app/private/hr-documents`. It is not publicly served and must not be linked with `storage:link`.

Future document modules must store metadata in the database and deliver files through authenticated controllers using policy authorization and streamed downloads. They must validate content type and size and use generated storage names. No document module exists in Phase 0.

## Production notes for 20i

Production must use at least these settings:

```dotenv
APP_ENV=production
APP_DEBUG=false
```

Before deployment, confirm with the selected 20i package:

- PHP 8.3+ and all required extensions
- MySQL versus MariaDB engine and supported version
- Web document root can point to Laravel's `public` directory
- SSH or command-runner access for Composer, migrations, and cache commands
- Cron support for `php artisan schedule:run`
- Queue worker availability and supervision strategy if queues are enabled later
- Outbound mail provider and sender-domain configuration for password resets
- Writable `storage` and `bootstrap/cache` directories
- TLS, backups, log retention, and database restore procedures

A typical deployment installs Composer dependencies without development packages, deploys assets produced by `npm run build`, runs `php artisan migrate --force`, and caches configuration/routes/views. Validate those steps against the actual 20i product before automating deployment.

Do not deploy with a local `.env`, development accounts, SQLite database, exposed storage symlink, or `APP_DEBUG=true`.
