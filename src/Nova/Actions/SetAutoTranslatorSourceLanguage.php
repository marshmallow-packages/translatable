<?php

namespace Marshmallow\Translatable\Nova\Actions;

use Illuminate\Bus\Queueable;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Actions\Action;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Laravel\Nova\Fields\ActionFields;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Nova\Http\Requests\NovaRequest;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Facades\Translatable;

class SetAutoTranslatorSourceLanguage extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        Cache::set('auto-translator-source-language', $fields->get('source'));
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        $auto_translator_source_language = Translatable::getAutoTranslatorSourceLanguage();

        return [
            Select::make(__('Source'), 'source')->options(
                Language::get()->pluck('name', 'id')
            )
                ->default($auto_translator_source_language->id)
                ->required()
                ->rules('required'),
        ];
    }
}
