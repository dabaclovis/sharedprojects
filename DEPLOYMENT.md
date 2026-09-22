# Deploying CD

The application has public articles, affiliate products, contact/about/policy pages, and authenticated account, publishing, product, and calendar screens. The support address is `info@auditlab.com`.

The framework has been upgraded to Laravel 12.69.2. Local verification passed 69 tests (572 assertions), Composer validation, PHP platform requirements, production cache compilation, and the production dependency security audit. The development `.env` remains a local configuration; configure the production template on your server before launch.

A temporary framework repair backup remains at `storage/framework/laravel-before-repair`; exclude it from deployment artifacts.

## Server setup

- Use PHP 8.2 or newer with Laravel's required extensions, Composer 2, and a persistent MySQL database (or explicitly configure another supported database). Enable PHP Fileinfo for image validation. Set `upload_max_filesize` to at least `5M` and `post_max_size` to at least `8M`.
- Serve **only the `public/` directory** through Apache or Nginx/PHP-FPM. Never expose the project root, `.env`, database files, or logs. `php artisan serve` is for local development.
- Set up the real domain and HTTPS. Configure trusted proxies for your actual load balancer if TLS terminates there; do not trust arbitrary proxies.
- Keep `storage/` persistent across releases, and make `storage/` and `bootstrap/cache/` writable by PHP. Do not use world-writable permissions.
- Copy `.env.production.example` to the server's `.env`, filling the real domain, database credentials, and SMTP provider credentials. The template deliberately contains no secrets. Do not overwrite your development `.env`.
- Verify the sender `info@auditlab.com` with the email provider, including its required DNS records. A mailbox address alone does not configure email delivery. Port/scheme depend on the provider (SMTP/587 with STARTTLS or SMTPS/465).
- Set actual social profile URLs; blank values hide the icons. Review the policy text against your actual hosting, third-party services, and retention practices before launch.

## First deployment

Run in the application directory on the server after configuring `.env`:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
composer audit --no-dev
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan deployment:check
```

Generate the application key only on a **new installation**. Preserve the existing key on updates. Migrations here must be run normally, never with `migrate:fresh` on a live database.

The current layout serves `public/css/app.css` directly and loads Bootstrap, Font Awesome, W3.CSS, jQuery, and Popper from external CDNs. It does not use a Vite manifest. Check CDN availability in the target environment. If you introduce `@vite` later, add a reproducible frontend install/build to deployment and deploy `public/build/`.

The contact form sends synchronously. If queued features are added, run a supervised `php artisan queue:work` process and restart it on deployment. No scheduled calendar notifications or external calendar synchronization are configured.

## Verification and updates

Run `php artisan test` in a development/CI installation with development dependencies and an isolated test database before releasing. Take and verify a database/upload backup before updates. Install from `composer.lock`, apply pending migrations, run `php artisan optimize`, and run `php artisan deployment:check` on each release. Do not run dependency updates directly on production.

Smoke-test on the real HTTPS domain:

1. Home, About, Contact, Policy, public articles, and products render; `/up` returns HTTP 200.
2. Registration creates a user and redirects to login. Login, logout, profile edits, and password changes work.
3. Guests cannot access account pages; regular users cannot access admin screens or other accounts' records.
4. Create/edit/read/delete an article; upload a product image and follow its affiliate link.
5. Click a calendar day, save an event, and check timezone conversion and editing.
6. Send a real contact message and confirm receipt and reply-to address in the support mailbox. Test failures do not claim successful delivery.

Back up both the database and `storage/app/public`; verify restoration. Monitor application errors and the `/up` endpoint. Store secrets outside source control. If a release fails, restore the previous code with its lockfile and compatible database backup; do not blindly roll back destructive migrations.

Hosting provider and domain have not yet been specified. No live deployment or real email delivery has been performed by this setup.
