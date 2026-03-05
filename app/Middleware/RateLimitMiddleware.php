<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\RateLimit;

class RateLimitMiddleware
{
    public function handle(Request $request): void
    {
        $ip = $request->ip();
        $method = $request->method();

        $limiter = new RateLimit();

        if (!$limiter->check($ip, $method)) {
            $remaining = $limiter->remainingSeconds($ip, $method);
            Response::error(
                "Troppi tentativi. Riprova tra {$remaining} secondi.",
                'RATE_LIMITED',
                429
            );
        }

        $limiter->record($ip, $method);
    }
}
