<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SmokeWebRoutesCommand extends Command
{
    protected $signature = 'app:smoke-routes {--user=admin@sns.com : Email of user to authenticate as}';

    protected $description = 'Smoke-test authenticated web GET routes for 500 errors';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->option('user'))->first();

        if ($user === null) {
            $this->error('User not found: '.$this->option('user'));

            return self::FAILURE;
        }

        $this->info('Smoke testing as '.$user->email.' (#'.$user->id.')');

        $kernel = app(Kernel::class);
        $failures = [];
        $passed = 0;
        $skipped = 0;

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $path = trim((string) $route->uri(), '/');
            if ($path === '') {
                $skipped++;

                continue;
            }

            $uri = '/'.$path;
            if (Str::contains($uri, '{')) {
                $resolved = $this->resolveUri($uri);
                if ($resolved === null) {
                    $skipped++;

                    continue;
                }
                $uri = $resolved;
            }

            if (in_array($uri, ['/', '/login', '/logout/idle'], true)) {
                $skipped++;

                continue;
            }

            try {
                Auth::login($user);
                $request = Request::create($uri, 'GET');
                $request->setUserResolver(fn () => $user);
                $response = $kernel->handle($request);
                $status = $response->getStatusCode();
                $kernel->terminate($request, $response);

                if ($status >= 500) {
                    $body = $response->getContent() ?: '';
                    $message = 'HTTP '.$status;
                    if (preg_match('/SQLSTATE\[([^\]]+)\][^<]*/', $body, $matches)) {
                        $message = Str::limit(trim(strip_tags($matches[0])), 180);
                    }
                    $failures[] = [$uri, $status, $message];
                } else {
                    $passed++;
                }
            } catch (Throwable $e) {
                $failures[] = [$uri, 500, Str::limit($e->getMessage(), 180)];
            } finally {
                Auth::logout();
            }
        }

        $this->newLine();
        $this->info("Passed: {$passed}, Skipped: {$skipped}, Failed: ".count($failures));

        if ($failures !== []) {
            $this->newLine();
            $this->error('Failures:');
            foreach ($failures as [$uri, $status, $message]) {
                $this->line("  [{$status}] {$uri}");
                $this->line("         {$message}");
            }

            return self::FAILURE;
        }

        $this->info('All smoke-tested routes OK.');

        return self::SUCCESS;
    }

    private function resolveUri(string $uri): ?string
    {
        $replacements = [
            '{invoice}' => $this->firstId('invoices'),
            '{project}' => $this->firstId('projects'),
            '{board}' => $this->firstId('boards'),
            '{id}' => $this->firstIdForRoute($uri),
        ];

        foreach ($replacements as $placeholder => $id) {
            if (! Str::contains($uri, $placeholder)) {
                continue;
            }

            if ($id === null) {
                return null;
            }

            $uri = str_replace($placeholder, (string) $id, $uri);
        }

        return Str::contains($uri, '{') ? null : $uri;
    }

    private function firstIdForRoute(string $uri): ?int
    {
        if (Str::contains($uri, 'payroll')) {
            return $this->firstId('payroll_runs');
        }

        if (Str::contains($uri, 'employees')) {
            return $this->firstId('employees');
        }

        return null;
    }

    private function firstId(string $table): ?int
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $row = DB::table($table)->orderByDesc('id')->first();

        return $row ? (int) $row->id : null;
    }
}
