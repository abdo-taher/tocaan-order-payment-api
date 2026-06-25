<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales.
     *
     * @var array<string>
     */
    private array $supported = ['en', 'ar'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language', config('app.locale'));

        // Parse the primary language tag (e.g., "ar-EG" → "ar", "en-US" → "en")
        $locale = strtolower(substr($locale, 0, 2));

        if (in_array($locale, $this->supported, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
