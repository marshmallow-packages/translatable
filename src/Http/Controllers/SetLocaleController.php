<?php

namespace Marshmallow\Translatable\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Marshmallow\Translatable\Models\Language;

class SetLocaleController extends Controller
{
	public function __invoke(Language $language)
	{
		Session::put('locale', $language->language);
        return redirect()->back();
	}
}
