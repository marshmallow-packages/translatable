<?php

namespace Marshmallow\Translatable;

use Illuminate\Support\Facades\Cache;
use Marshmallow\Translatable\Models\Language;

class Translatable
{
    protected $languages = [];

    public function appDefaultLanguage(): string
    {
        return config('app.default_locale') ?? config('app.locale') ?? 'en';
    }

    public function deeplTranslaterIsActive(): bool
    {
        return config('translatable.deepl.api_path') &&
            config('translatable.deepl.api_key');
    }

    public function getAutoTranslatorSourceLanguage(): Language
    {
        if ($language_id = Cache::get('auto-translator-source-language')) {
            return Language::find($language_id);
        }
        $default = $this->appDefaultLanguage();
        $language = Language::where('language', $default)->first();
        if ($language) {
            return $language;
        }

        return Language::first();
    }

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
