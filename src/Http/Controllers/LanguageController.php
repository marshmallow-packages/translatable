<?php

namespace Marshmallow\Translatable\Http\Controllers;

use Illuminate\Routing\Controller;
use Marshmallow\Translatable\Http\Resources\LanguageTogglerResource;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Scanner\Drivers\Translation;

class LanguageController extends Controller
{
    private $translation;

    public function __construct(Translation $translation)
    {
        $this->translation = $translation;
    }

    public function index()
    {
        return LanguageTogglerResource::collection(Language::get());
    }
}
