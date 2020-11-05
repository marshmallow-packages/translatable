<?php

namespace Marshmallow\Translatable\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Marshmallow\Translatable\Models\Language;

class SetLocaleController extends Controller
{
    public function __invoke(Language $language, Request $request)
    {
        $request->setUserLocale($language);

        return redirect()->back();
    }
}
