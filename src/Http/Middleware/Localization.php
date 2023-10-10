<?php

namespace Marshmallow\Translatable\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Marshmallow\Translatable\Models\Language;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $locale_key = 'user-locale';
        $force_locale_string = config('translatable.force_locale_query_string', 'force_locale');

        if ($request->has($force_locale_string)) {

            /** Set the locale if we can find a matching language */
            $locale = $request->get($force_locale_string);
            $language = Language::where('language', $locale)->first();
            if ($language) {
                $request->setUserLocale($language);
            }

            /** Make sure we keep other query parameters. */
            $query_string = $request->query();
            unset($query_string[$force_locale_string]);
            $query_string = !empty($query_string) ? '?' . http_build_query($query_string) : '';

            /** Redirect back without the force locale query parameter. */
            return redirect()->to(
                $request->path() . $query_string
            );
        } elseif (Session::has($locale_key)) {
            $locale = Session::get($locale_key);
        } else if (Cache::has($locale_key)) {
            $locale = Cache::get($locale_key);
        } else {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
