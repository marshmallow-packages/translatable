<?php

namespace Marshmallow\Translatable\Http\Controllers;

use Illuminate\Routing\Controller;
use Marshmallow\Translatable\Scanner\Drivers\Translation;
use Marshmallow\Translatable\Http\Resources\LanguageTogglerResource;

class LanguageController extends Controller
{
    private $translation;

    public function __construct(Translation $translation)
    {
        $this->translation = $translation;
    }

    public function index()
    {
        return LanguageTogglerResource::collection(config('translatable.models.language')::get());
    }
}
