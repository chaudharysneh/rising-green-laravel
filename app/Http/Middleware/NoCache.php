<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to prevent browser caching of authenticated pages.
 * 
 * This middleware adds cache prevention headers to all responses,
 * ensuring that:
 * - Authenticated pages cannot be accessed from browser cache after logout
 * - Back button doesn't show cached authenticated content
 * - Forward button doesn't reopen protected pages after logout
 */
class NoCache
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent browser caching of authenticated pages
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');

        return $response;
    }
}
