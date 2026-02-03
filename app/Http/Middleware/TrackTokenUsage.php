<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackTokenUsage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Process the request first
        $response = $next($request);

        // Update last_used_at if user is authenticated via token
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $token = $user->currentAccessToken();

            // Only update if last update was more than 1 minute ago to reduce DB writes
            if (!$token->last_used_at || $token->last_used_at->lt(now()->subMinute())) {
                $token->last_used_at = now();
                $token->save();
            }
        }

        return $response;
    }
}
