<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request and configure the application locale.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = array_keys((array) config('app.available_locales', ['en' => 'English']));
        $defaultLocale = (string) config('app.locale', 'en');

        $locale = $this->resolveLocale($request, $availableLocales, $defaultLocale);

        app()->setLocale($locale);

        $response = $next($request);

        $response->headers->set('Content-Language', $locale);

        return $response;
    }

    /**
     * Resolve the preferred locale from request headers, query parameters, or fallback.
     *
     * @param  array<string>  $availableLocales
     */
    protected function resolveLocale(Request $request, array $availableLocales, string $defaultLocale): string
    {
        // 1. Check custom X-Locale or X-Language header
        $customHeader = $request->header('X-Locale') ?: $request->header('X-Language');
        if ($customHeader && in_array(strtolower(trim((string) $customHeader)), $availableLocales, true)) {
            return strtolower(trim((string) $customHeader));
        }

        // 2. Check query parameter ?locale=...
        $queryLocale = $request->query('locale');
        if (is_string($queryLocale) && in_array(strtolower(trim($queryLocale)), $availableLocales, true)) {
            return strtolower(trim($queryLocale));
        }

        // 3. Check Accept-Language HTTP header
        $preferredLanguage = $request->getPreferredLanguage($availableLocales);
        if ($preferredLanguage && in_array($preferredLanguage, $availableLocales, true)) {
            return $preferredLanguage;
        }

        return $defaultLocale;
    }
}
