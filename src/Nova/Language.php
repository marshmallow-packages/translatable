<?php

namespace Marshmallow\Translatable\Nova;

use App\Nova\Resource;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\HasMany;
use Marshmallow\AdvancedImage\AdvancedImage;
use Marshmallow\Translatable\Traits\TranslatableFields;

class Language extends Resource
{
    use TranslatableFields;

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
        return __('Languages');
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return __('Language');
    }

    public static $group_icon = '<svg xmlns="http://www.w3.org/2000/svg" class="sidebar-icon" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path fill="var(--sidebar-icon)" d="M23 7V1h-6v2H7V1H1v6h2v10H1v6h6v-2h10v2h6v-6h-2V7h2zM3 3h2v2H3V3zm2 18H3v-2h2v2zm12-2H7v-2H5V7h2V5h10v2h2v10h-2v2zm4 2h-2v-2h2v2zM19 5V3h2v2h-2zm-5.27 9h-3.49l-.73 2H7.89l3.4-9h1.4l3.41 9h-1.63l-.74-2zm-3.04-1.26h2.61L12 8.91l-1.31 3.83z"/></svg>';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'Marshmallow\Translatable\Models\Language';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name', 'language',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Illuminate\Http\Request $request Request
     *
     * @return array
     */
    public function translatableFields(Request $request)
    {
        return [
            Text::make(__('Name'), 'name')
                ->sortable()
                ->rules([
                    'required',
                ]),

            Text::make(__('ISO'), 'language')
                ->sortable()
                ->rules([
                    'required',
                    'min:2',
                    'max:2',
                ]),

            AdvancedImage::make(__('Icon'), 'icon')
                ->croppable(
                    config('translatable.flag_icon.height') / config('translatable.flag_icon.width')
                )->resize(
                    config('translatable.flag_icon.height'),
                    config('translatable.flag_icon.width')
                ),

            Number::make(__('Translations'))
                ->onlyOnIndex()
                ->resolveUsing(function ($collection, $language, $param) {
                    return $language->translations->count();
                }),

            HasMany::make(__('Translations'), 'translations', Translation::class),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
