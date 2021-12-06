<?php

namespace Marshmallow\Translatable\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

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

        if (Session::has($locale_key)) {
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
