<?php

namespace Marshmallow\Translatable\Http\Controllers;

use Illuminate\Routing\Controller;
use Marshmallow\Translatable\Http\Resources\LanguageTogglerResource;

class LanguageController extends Controller
{
    public function index()
    {
        return LanguageTogglerResource::collection(config('translatable.models.language')::get());
    }
}
