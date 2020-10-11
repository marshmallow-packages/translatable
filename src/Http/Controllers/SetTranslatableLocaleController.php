<?php

namespace Marshmallow\Translatable\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Marshmallow\Translatable\Models\Language;

class SetTranslatableLocaleController extends Controller
{
    public function __invoke(Language $language, Request $request)
    {
        $request->setTranslatableLocale($language);

        return redirect()->back();
    }
}
