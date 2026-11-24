<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add the HTTP security headers to every response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Allow the Vite dev server (npm run dev) only in local environment
        $vite = app()->environment('local') ? ' http://localhost:5173 http://127.0.0.1:5173 http://[::1]:5173' : '';

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; img-src 'self' data:; style-src 'self'".$vite."; script-src 'self'".$vite.
            "; connect-src 'self'".str_replace('http', 'ws', $vite)."; frame-ancestors 'none'; form-action 'self' https://checkout.stripe.com; base-uri 'self'");

        return $response;
    }
}
