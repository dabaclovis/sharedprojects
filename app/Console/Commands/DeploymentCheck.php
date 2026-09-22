<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeploymentCheck extends Command
{
    protected $signature = 'deployment:check';
    protected $description = 'Check production configuration without displaying secrets';

    public function handle(): int
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST);
        $checks = [
            'Production environment' => app()->environment('production'),
            'Debug disabled' => ! config('app.debug'),
            'Application key configured' => filled(config('app.key')),
            'Real HTTPS application URL' => str_starts_with(config('app.url'), 'https://') && $host && ! str_ends_with($host, '.example') && $host !== 'localhost',
            'Secure session cookies' => (bool) config('session.secure'),
            'HTTP-only session cookies' => (bool) config('session.http_only'),
            'Support email configured' => (bool) filter_var(config('site.support_email'), FILTER_VALIDATE_EMAIL),
            'Delivery mailer configured' => ! in_array(config('mail.default'), ['log', 'array'], true),
            'Storage writable' => is_writable(storage_path()) && is_writable(storage_path('framework/views')),
            'Bootstrap cache writable' => is_writable(base_path('bootstrap/cache')),
            'Public uploads linked' => is_dir(public_path('storage')) && realpath(public_path('storage')) === realpath(Storage::disk('public')->path('')),
            'No development Vite server file' => ! file_exists(public_path('hot')),
        ];
        if (config('mail.default') === 'smtp') {
            $checks['SMTP host configured'] = filled(config('mail.mailers.smtp.host')) && ! in_array(config('mail.mailers.smtp.host'), ['localhost', '127.0.0.1'], true);
        }
        try {
            DB::connection()->getPdo();
            $checks['Database connection'] = true;
            $migrator = app('migrator');
            $checks['All migrations applied'] = $migrator->repositoryExists()
                && count(array_diff(array_keys($migrator->getMigrationFiles(database_path('migrations'))), $migrator->getRepository()->getRan())) === 0;
        } catch (\Throwable $exception) {
            $checks['Database connection and migrations'] = false;
        }
        foreach ($checks as $label => $passed) {
            $passed ? $this->info('PASS: '.$label) : $this->error('FAIL: '.$label);
        }
        $this->comment('Also verify email delivery, browser flows, backups, DNS/TLS, and dependency audits on the deployment server.');

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
