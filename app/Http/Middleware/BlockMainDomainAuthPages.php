<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BlockMainDomainAuthPages
{
    public function handle(Request $request, Closure $next)
    {
        $host = strtolower($request->getHost());
        $normalizedHost = preg_replace('/^www\./', '', $host) ?: $host;

        $configuredDomain = config('app.domain');
        $fallbackUrl = config('app.url');

        $candidate = $configuredDomain ?: $fallbackUrl;
        $candidateHost = parse_url($candidate, PHP_URL_HOST) ?: $candidate;
        $candidateHost = strtolower($candidateHost);
        $normalizedCandidate = preg_replace('/^www\./', '', $candidateHost) ?: $candidateHost;

        $path = trim($request->path(), '/');

        $authPaths = [
            'login',
            'register',
            'email/verify',
            'forgot-password',
            'reset-password',
            'two-factor-challenge',
        ];

        if ($normalizedHost === $normalizedCandidate && in_array($path, $authPaths, true)) {
            Log::warning('Blocked backoffice auth access on main domain', [
                'host' => $host,
                'normalized_host' => $normalizedHost,
                'app_domain' => $configuredDomain,
                'app_url' => $fallbackUrl,
                'path' => $path,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            abort(404);
        }

        return $next($request);
    }
}
