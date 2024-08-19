<?php

namespace Marshmallow\Translatable\Nova;

use App\Nova\Resource;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\BelongsTo;
use Marshmallow\LiveUpdate\TextLiveUpdate;
use Laravel\Nova\Http\Requests\NovaRequest;
use Marshmallow\Translatable\Nova\Language;
use Marshmallow\Translatable\Facades\Translatable;
use Marshmallow\Translatable\Nova\Filters\LanguageFilter;
use Marshmallow\Translatable\Action\TranslateUsingDeeplAction;
use Marshmallow\Translatable\Models\Language as LanguageModel;
use Marshmallow\Translatable\Nova\Filters\NoTranslationAvailableFilter;
use Marshmallow\Translatable\Nova\Actions\SetAutoTranslatorSourceLanguage;

class Translation extends Resource
{
    public static $clickAction = 'ignore';

    /**
     * Get the logical group associated with the resource.
     *
     * @return string
     */
    public static function group()
    {
        return __('Translation');
    }

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return __('Translations');
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return __('Translation');
    }

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'Marshmallow\Translatable\Models\Translation';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'value';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'key',
        'value',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request NovaRequest
     *
     * @return array
     */

    public function fieldsForCreate(NovaRequest $request)
    {
        return [
            BelongsTo::make(__('Language'), 'language', Language::class),

            Text::make(__('Group'), 'group')
                ->sortable()
                ->required()
                ->default('single'),

            Text::make(__('Key'), 'key')
                ->sortable()
                ->asHtml()
                ->required(),

            Textarea::make(__('Value'), 'value')
                ->sortable()
                ->required(),
        ];
    }


    public function fields(NovaRequest $request)
    {
        $auto_translator_source_language = Translatable::getAutoTranslatorSourceLanguage();
        return [

            BelongsTo::make(__('Language'), 'language', Language::class)
                ->readonly(),

            Text::make(__('Group'), 'group')
                ->sortable()
                ->required()
                ->default('single')
                ->readonly(),

            TextLiveUpdate::make(__('Key'), 'key')
                ->sortable()
                ->displayUsing(function ($value) {
                    $value = strip_tags($value);
                    return Str::of($value)->limit(100);
                })
                ->required()
                ->readonly()
                ->copyable()
                ->copyableTo(
                    __('Value'),
                    __('Use this value'),
                )
                ->copyableWithAction(
                    when: function () {
                        return Translatable::deeplTranslaterIsActive();
                    },
                    action: TranslateUsingDeeplAction::class,
                    icon: 'translate',
                    target_field_label: __('Value'),
                    tooltip: __('Translate value from :source to :target with Deepl', ['source' => $auto_translator_source_language->name, 'target' => $this->language?->name]),
                )
                ->asPlaceholder()
                ->exceptOnForms(),

            Textarea::make(__('Key'), 'key')
                ->sortable()
                ->required()
                ->readonly()
                ->onlyOnForms(),

            TextLiveUpdate::make(__('Value'), 'value')
                ->onlyOnIndex(),

            Textarea::make(__('Value'), 'value')
                ->sortable()
                ->required()
                ->hideFromIndex(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [
            new LanguageFilter(),
            new NoTranslationAvailableFilter(),
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        $actions = [];

        if (Translatable::deeplTranslaterIsActive()) {
            $actions[] = SetAutoTranslatorSourceLanguage::make()->standalone();
        }

        return $actions;
    }
}
