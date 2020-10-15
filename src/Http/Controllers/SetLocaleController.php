<?php

namespace Marshmallow\Translatable\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Marshmallow\Translatable\Models\Language;

class SetLocaleController extends Controller
{
    public function __invoke(Language $language, Request $request)
    {
        $request->setUserLocale($language);

        return redirect()->back();
    }
}
