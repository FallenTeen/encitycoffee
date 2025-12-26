<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // If this is an API request with Authorization header, validate the token
        if ($request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            
            // If token doesn't exist, return 401
            if (!$token) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            
            // If token exists but is expired, return 401
            if ($token->expires_at && $token->expires_at->isPast()) {
                return response()->json(['message' => 'Token has expired.'], 401);
            }
        }
        
        return $next($request);
    }
}