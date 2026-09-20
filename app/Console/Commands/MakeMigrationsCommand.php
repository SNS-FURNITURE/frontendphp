<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Django-style entry point: python manage.py makemigrations
 *
 * Laravel keeps schema in database/migrations/*.php (already generated
 * from the Node ERP schema). This command verifies those files exist and
 * reports pending migrations for the connected database.
 */
class MakeMigrationsCommand extends Command
{
    protected $signature = 'makemigrations
                            {name? : Optional name for a NEW empty migration stub}
                            {--create= : Table name hint for php artisan make:migration}';

    protected $description = 'Django-style makemigrations: verify ERP schema migrations (or stub a new one)';

    public function handle(): int
    {
        $migrationPath = database_path('migrations');
        $files = collect(File::files($migrationPath))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.php'))
            ->values();

        $this->info('SNS ERP — makemigrations (Laravel)');
        $this->line('Connected DB: '.config('database.default').' / '.config('database.connections.'.config('database.default').'.database'));
        $this->newLine();

        if ($files->isEmpty()) {
            $this->error('No migration files found in database/migrations.');
            $this->line('Expected ERP migrations (from Node schema) are missing.');

            return self::FAILURE;
        }

        $this->info("Found {$files->count()} migration file(s):");
        foreach ($files as $file) {
            $this->line('  - '.$file->getFilename());
        }
        $this->newLine();

        // Optional: create an extra stub when the developer passes a name
        // (same idea as Django creating a new migration module).
        if ($name = $this->argument('name')) {
            $args = ['name' => $name];
            if ($create = $this->option('create')) {
                $args['--create'] = $create;
            }
            $this->call('make:migration', $args);
            $this->newLine();
        }

        $this->comment('Schema source: Node ERP (sns-erp-backend init.ts) → Laravel migrations.');
        $this->comment('Next step (like Django migrate):');
        $this->line('  php artisan migrate');
        $this->line('  php artisan migrate --seed   # also seed demo roles/users');
        $this->newLine();

        $this->call('migrate:status');

        return self::SUCCESS;
    }
}
