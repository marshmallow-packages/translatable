<?php

namespace Marshmallow\Translatable\Http\Middleware;

use Marshmallow\Translatable\TranslationTool;

class Authorize
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, $next)
    {
        return resolve(TranslationTool::class)->authorize($request) ? $next($request) : abort(403);
    }
}
