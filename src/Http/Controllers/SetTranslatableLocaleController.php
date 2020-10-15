<?php

namespace Marshmallow\Translatable\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Marshmallow\Translatable\Models\Language;

class SetTranslatableLocaleController extends Controller
{
    public function __invoke(Language $language, Request $request)
    {
        $request->setTranslatableLocale($language);
        return redirect()->back();
    }
}
