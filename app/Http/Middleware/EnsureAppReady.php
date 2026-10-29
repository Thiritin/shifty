<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

// Runs before the session starts, so nothing here may depend on the database being usable.
class EnsureAppReady
{
    public function handle(Request $request, Closure $next): Response
    {
        if (blank(config('app.key'))) {
            $key = 'base64:'.base64_encode(random_bytes(32));
            // Terminating middleware still builds the cookie encrypter, which throws without a key.
            config(['app.key' => $key]);

            return $this->setupPage('key', ['suggestedKey' => $key]);
        }

        $connection = config('database.connections.'.config('database.default'));
        if ($connection['driver'] === 'sqlite' && $connection['database'] !== ':memory:' && ! file_exists($connection['database'])) {
            return $this->setupPage('database');
        }

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            return $this->setupPage('unreachable', [
                'target' => $connection['driver'] === 'sqlite' ? null : ($connection['host'] ?? '').':'.($connection['port'] ?? ''),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ]);
        }

        // Per request, not at boot: Octane reuses the booted config across requests.
        Setting::apply();

        if (! config('shifty.installed')) {
            if (! Schema::hasTable('settings')) {
                Artisan::call('migrate', ['--force' => true]);
                Setting::apply();
            }

            if (! $request->is('install', 'brand/*')) {
                return redirect('/install');
            }
        }

        return $next($request);
    }

    private function setupPage(string $problem, array $data = []): Response
    {
        return response()->view('setup', ['problem' => $problem, ...$data], 503);
    }
}
