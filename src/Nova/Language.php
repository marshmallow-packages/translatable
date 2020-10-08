<?php

namespace Marshmallow\Translatable\Nova;

use App\Nova\Resource;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\HasMany;
use Illuminate\Database\Eloquent\Model;
use Marshmallow\AdvancedImage\AdvancedImage;
use Marshmallow\Translatable\Nova\Translation;
use Marshmallow\Translatable\Models\Language as LanguageModel;

class Language extends Resource
{
	public static $group = 'Translation';
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
    public function fields(Request $request)
    {
        return [
            Text::make(__('Name'), 'name')
            	->sortable()
            	->rules([
            		'required'
            	]),

            Text::make(__('ISO'), 'language')
            	->sortable()
            	->rules([
            		'required',
            		'min:2',
            		'max:2'
            	]),

            AdvancedImage::make(__('Icon'), 'icon')
            			->croppable(
            				config('translatable.flagicon.height')/config('translatable.flagicon.width')
            			)->resize(
            				config('translatable.flagicon.height'),
            				config('translatable.flagicon.width')
            			),

            Number::make(__('Translations'))
            	->onlyOnIndex()
            	->resolveUsing(function ($collection, LanguageModel $language, $param) {
            		return $language->translations->count();
            	}),

            HasMany::make(__('Translations'), 'translations', Translation::class),
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
        return [];
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
