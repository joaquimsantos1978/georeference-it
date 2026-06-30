<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED = [
        'en'    => 'English',
        'ar'    => 'العربية',
        'zh-CN' => '简体中文',
        'fr'    => 'Français',
        'ru'    => 'Русский',
        'es'    => 'Español',
        'zh-TW' => '繁體中文',
        'cs'    => 'Čeština',
        'ja'    => '日本語',
        'pl'    => 'Polski',
        'pt'    => 'Português',
        'uk'    => 'Українська',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale')
            ?? auth()->user()?->locale
            ?? $this->detectBrowserLocale($request)
            ?? config('app.locale');

        if (!array_key_exists($locale, self::SUPPORTED)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }

    // Matches the browser's Accept-Language header against supported locales
    // (only used when no explicit session/user preference exists yet)
    private function detectBrowserLocale(Request $request): ?string
    {
        $supportedKeys = array_keys(self::SUPPORTED);

        foreach ($request->getLanguages() as $lang) {
            // Exact match (case-insensitive), e.g. "zh-tw" -> "zh-TW"
            foreach ($supportedKeys as $supported) {
                if (strcasecmp($lang, $supported) === 0) {
                    return $supported;
                }
            }
            // Fallback to base language match, e.g. "fr-be" -> "fr"
            $short = strtolower(substr($lang, 0, 2));
            foreach ($supportedKeys as $supported) {
                if (strtolower(substr($supported, 0, 2)) === $short) {
                    return $supported;
                }
            }
        }

        return null;
    }
}
