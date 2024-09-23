<?php

namespace Marshmallow\Translatable\Action;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Marshmallow\Translatable\Facades\Translatable;
use Marshmallow\LiveUpdate\CopyableActionInterface;

class TranslateUsingDeeplAction implements CopyableActionInterface
{
    public function execute(Model $model): ?string
    {
        $auto_translator_source_language = Translatable::getAutoTranslatorSourceLanguage();

        $response = $this->raw(
            source: $auto_translator_source_language->language,
            target: $model->language->language,
            text: $model->key,
        );

        return $response ?? $model->key;
    }

    public function raw($source, $target, $text)
    {
        $response = Http::withHeaders([
            'Authorization' => 'DeepL-Auth-Key ' . config('translatable.deepl.api_key'),
        ])->post(config('translatable.deepl.api_path') . '/v2/translate', [
            'text' => [
                $text,
            ],
            'target_lang' => (string) Str::of($target)->upper(),
            'source_lang' => (string) Str::of($source)->upper(),
            'tag_handling' => 'html',
        ]);

        return Arr::get($response->json(), 'translations.0.text');
    }
}
