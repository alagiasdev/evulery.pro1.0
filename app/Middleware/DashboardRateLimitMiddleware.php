<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\RateLimit;

/**
 * Rate limit POST requests on dashboard routes.
 * Uses a separate endpoint key ('DASHBOARD_POST') to not conflict with API limits.
 * Limit: 30 POST requests per minute per IP (generous for normal use, blocks abuse).
 */
class DashboardRateLimitMiddleware
{
    public function handle(Request $request): void
    {
        // Only limit POST requests (GET navigation is unrestricted)
        if ($request->method() !== 'POST') {
            return;
        }

        $ip = $request->ip();
        $key = 'DASHBOARD_POST';
        $maxRequests = 30;
        $windowSeconds = 60;

        $limiter = new RateLimit();

        if (!$limiter->checkCustom($ip, $key, $maxRequests, $windowSeconds)) {
            flash('error', 'Troppe richieste. Attendi qualche secondo e riprova.');
            Response::back();
        }

        $limiter->recordCustom($ip, $key);
    }
}