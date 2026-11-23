<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Language of the customer pages: English or Filipino.
 * The visitor's own choice (?lang=en|fil, remembered in their session) wins; otherwise a browser set
 * to Filipino/Tagalog gets Filipino; otherwise the business's default page language.
 */
class PageLanguage
{
    public const LANGUAGES = ['en' => 'English', 'fil' => 'Filipino'];

    public function handle(Request $request, Closure $next): Response
    {
        $choice = $request->query('lang');
        if (is_string($choice) && isset(self::LANGUAGES[$choice]) && $request->hasSession()) {
            $request->session()->put('page_lang', $choice);
        }
        $business = $request->route('business');
        $locale = ($request->hasSession() ? $request->session()->get('page_lang') : null)
            ?? (preg_match('/^\s*(fil|tl)\b/i', (string) $request->header('Accept-Language')) ? 'fil' : null)
            ?? ($business instanceof Business ? $business->setting('language', 'en') : 'en');
        app()->setLocale(isset(self::LANGUAGES[$locale]) ? $locale : 'en');

        return $next($request);
    }
}
