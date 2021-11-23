<?php

namespace Marshmallow\Translatable\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Marshmallow\Translatable\Models\Language;

class SetTranslatableLocaleController extends Controller
{
    public function setLocale(Language $language, Request $request)
    {
        $request->setTranslatableLocale($language);
        return redirect()->back();
    }

    public static function fallback(Request $request)
    {
        $languageModel  = (config('translatable.models.language'));
        $language       = $languageModel::resolveRoute($request->route('language'));

        $request->setTranslatableLocale($language);
        return redirect()->back();
    }
}
