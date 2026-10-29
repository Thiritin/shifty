<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Per request, not at boot: Octane reuses the booted config across requests.
class ApplySettings
{
    public function handle(Request $request, Closure $next): Response
    {
        Setting::apply();

        return $next($request);
    }
}
