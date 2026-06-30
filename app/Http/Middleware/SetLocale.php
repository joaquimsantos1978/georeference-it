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
        $locale = session('locale', config('app.locale'));

        if (!array_key_exists($locale, self::SUPPORTED)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
