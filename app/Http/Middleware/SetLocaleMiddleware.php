<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        App::setLocale($locale);

        // اگر کاربر لاگین است، فقط در صورت تغییر، کش را آپدیت کن
        if ($request->user()) {
            $cacheKey = "user_locale_{$request->user()->id}";
            $cached = Cache::get($cacheKey);
            if ($cached !== $locale) {
                Cache::put($cacheKey, $locale, now()->addDays(30));
            }
        }

        /** @var Response $response */
        $response = $next($request);

        // برای پاسخ‌های JSON هدر Content-Language را اضافه کن (Content-Type را دست نزن)
        if ($response instanceof JsonResponse) {
            $response->headers->set('Content-Language', $locale);
        }

        return $response;
    }

    private function resolveLocale(Request $request): string
    {
        $supported = config('app.supported_locales', ['en']);
        $fallback = config('app.fallback_locale', 'en');
        $default = config('app.locale', 'en');

        // اولویت: ?lang= → X-Locale → کش/پروفایل کاربر → Accept-Language → default
        $fromQuery = $request->query('lang');
        $fromHeader = $request->header('X-Locale');
        $fromUser = $request->user()
            ? (Cache::get("user_locale_{$request->user()->id}") ?? $request->user()->locale)
            : null;
        $fromAccept = $this->fromAcceptLanguage($request->header('Accept-Language'), $supported);

        $candidate = $fromQuery ?: ($fromHeader ?: ($fromUser ?: ($fromAccept ?: $default)));
        return $this->normalize($candidate, $supported, $fallback);
    }

    private function fromAcceptLanguage(?string $header, array $supported): ?string
    {
        if (!$header) return null;

        // q-weighted parse
        $langs = [];
        foreach (explode(',', $header) as $part) {
            $segments = explode(';', trim($part));
            $code = trim($segments[0]);
            $q = 1.0;
            if (isset($segments[1])) {
                $qPart = trim($segments[1]);
                if (str_starts_with($qPart, 'q=')) {
                    $q = (float)substr($qPart, 2);
                }
            }
            $langs[$code] = $q;
        }
        arsort($langs, SORT_NUMERIC);

        foreach (array_keys($langs) as $code) {
            $norm = strtolower(str_replace('_', '-', $code));
            $norm = explode('-', $norm)[0];
            if (in_array($norm, $supported, true)) {
                return $norm;
            }
        }
        return null;
    }

    private function normalize(?string $candidate, array $supported, string $fallback): string
    {
        if (!$candidate) return $fallback;

        $norm = strtolower(str_replace('_', '-', $candidate));
        $norm = explode('-', $norm)[0]; // fa-IR -> fa

        return in_array($norm, $supported, true) ? $norm : $fallback;
    }
}
