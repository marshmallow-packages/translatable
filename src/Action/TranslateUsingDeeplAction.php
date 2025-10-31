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

    public function raw($source, $target, $text, $html_handling = true)
    {
        // Protect placeholders by wrapping them in XML tags that DeepL won't translate
        $protectedText = $this->protectPlaceholders($text);

        $post_data = [
            'text' => [
                $protectedText,
            ],
            'target_lang' => (string) Str::of($target)->upper(),
            'source_lang' => (string) Str::of($source)->upper(),
        ];

        if ($html_handling) {
            $post_data['tag_handling'] = 'html';
        }

        // Tell DeepL to ignore content within <keep> tags
        $post_data['ignore_tags'] = 'keep';

        $response = Http::withHeaders([
            'Authorization' => 'DeepL-Auth-Key ' . config('translatable.deepl.api_key'),
        ])->post(config('translatable.deepl.api_path') . '/v2/translate', $post_data);

        $translatedText = Arr::get($response->json(), 'translations.0.text');

        // Restore the placeholders from the protected format
        return $this->restorePlaceholders($translatedText);
    }

    /**
     * Protect Laravel/Vue placeholders from being translated
     * Supports: {variable}, :variable, {{variable}}, {!! variable !!}
     */
    protected function protectPlaceholders($text)
    {
        if (empty($text)) {
            return $text;
        }

        // Protect Laravel blade placeholders like {{variable}} or {!! variable !!}
        $text = preg_replace('/(\{\{!?\s*.*?\s*!?\}\})/', '<keep>$1</keep>', $text);

        // Protect Laravel/Vue curly brace placeholders like {variable}
        $text = preg_replace('/(\{[a-zA-Z_][a-zA-Z0-9_]*\})/', '<keep>$1</keep>', $text);

        // Protect Laravel colon placeholders like :variable
        $text = preg_replace('/(:[a-zA-Z_][a-zA-Z0-9_]*)/', '<keep>$1</keep>', $text);

        return $text;
    }

    /**
     * Restore placeholders after translation
     */
    protected function restorePlaceholders($text)
    {
        if (empty($text)) {
            return $text;
        }

        // Remove the <keep> tags while preserving the content
        return preg_replace('/<keep>(.*?)<\/keep>/', '$1', $text);
    }
}
