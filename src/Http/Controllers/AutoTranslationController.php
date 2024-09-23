<?php

namespace Marshmallow\Translatable\Http\Controllers;

use Exception;
use Laravel\Nova\Nova;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;
use Marshmallow\Translatable\Fields\LanguageToggler;
use Marshmallow\Translatable\Action\TranslateUsingDeeplAction;

class AutoTranslationController extends Controller
{
    public function settings()
    {
        return response()->json([
            'active' => config('translatable.auto_translator.active'),
            'button_text' => __('Translate with DeepL'),
            'loading_text' => __('Translating...'),
        ]);
    }

    public function translate(NovaRequest $request)
    {
        /**
         * If we get an empty string of the target and source are the same,
         * we don't spend a request to the API and return an empty string.
         */
        if (!$request->text || $request->source === $request->target) {
            return response()->json([
                'text' => '',
            ]);
        }

        /** Run the Deepl translator */
        $translation = (new TranslateUsingDeeplAction)->raw(
            source: $request->source,
            target: $request->target,
            text: $request->text,
        );

        return response()->json([
            'text' => $translation,
        ]);
    }
}
