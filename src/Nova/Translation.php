<?php

namespace Marshmallow\Translatable\Nova;

use App\Nova\Resource;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Marshmallow\LiveUpdate\TextLiveUpdate;
use Laravel\Nova\Http\Requests\NovaRequest;
use Marshmallow\Translatable\Nova\Filters\LanguageFilter;
use Marshmallow\Translatable\Nova\Filters\NoTranslationAvailableFilter;

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
        'key', 'value',
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
        return [

            BelongsTo::make(__('Language'), 'language', Language::class)
                ->readonly(),

            Text::make(__('Group'), 'group')
                ->sortable()
                ->required()
                ->default('single')
                ->readonly(),

            Text::make(__('Key'), 'key')
                ->sortable()
                ->displayUsing(function ($value) {
                    $value = strip_tags($value);
                    return Str::of($value)->limit(100);
                })
                ->required()
                ->readonly()
                ->copyable()
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
        return [];
    }
}
