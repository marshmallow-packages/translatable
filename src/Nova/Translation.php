<?php

namespace Marshmallow\Translatable\Nova;

use App\Nova\Resource;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Marshmallow\LiveUpdate\TextLiveUpdate;
use Marshmallow\Translatable\Nova\Filters\LanguageFilter;
use Marshmallow\Translatable\Nova\Filters\NoTranslationAvailableFilter;

class Translation extends Resource
{
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
     * @param \Illuminate\Http\Request $request Request
     *
     * @return array
     */
    public function fields(Request $request)
    {
        return [

            BelongsTo::make(__('Language'), 'language', Language::class),

            Text::make(__('Group'), 'group')
                ->sortable()
                ->rules([
                    'required',
                ])
                ->default('single'),

            Text::make(__('Key'), 'key')
                ->sortable()
                ->resolveUsing(function ($value) {
                    $value_array = str_split($value, 75);
                    return join("<br/>", $value_array);
                })
                ->asHtml()
                ->rules([
                    'required',
                ]),

            TextLiveUpdate::make(__('Value'), 'value')->onlyOnIndex(),

            Textarea::make(__('Value'), 'value')
                ->sortable()
                ->rules([
                    'required',
                ]),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [
            new LanguageFilter,
            new NoTranslationAvailableFilter,
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
