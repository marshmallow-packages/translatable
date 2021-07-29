<?php

namespace Marshmallow\Translatable;

class Translatable
{
    protected $languages = [];

    public function getLanguageByTranslationParameter($language_identifier)
    {
        if (array_key_exists($language_identifier, $this->languages)) {
            return $this->languages[$language_identifier];
        }
        $language = config('translatable.models.language')::where('language', $language_identifier)->firstOrFail();

        $this->languages[$language_identifier] = $language;

        return $language;
    }
}
