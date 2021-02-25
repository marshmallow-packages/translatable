<?php

namespace Marshmallow\Translatable\Nova;

use App\Nova\Resource;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\HasMany;
use Illuminate\Database\Eloquent\Model;
use Marshmallow\AdvancedImage\AdvancedImage;
use Marshmallow\Translatable\Traits\TranslatableFields;
use Marshmallow\Translatable\Models\Language as LanguageModel;

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

    public static $group_icon = '<svg class="sidebar-icon" viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g id="icon-shape"><path fill="var(--sidebar-icon)" d="M17.747965,12 C17.912494,11.3607602 18,10.6905991 18,10 C18,9.30940086 17.912494,8.63923984 17.747965,8 L13.9319635,8 C13.9770158,8.64627227 14,9.31512813 14,10 C14,10.6848719 13.9770158,11.3537277 13.9319635,12 L17.747965,12 L17.747965,12 Z M16.9297424,14 C15.9997274,15.6077187 14.5262862,16.8617486 12.7605851,17.5109236 C13.1807787,16.5491202 13.5012461,15.3524505 13.7109556,14 L16.9297424,14 L16.9297424,14 Z M8.08134222,12 C8.02912147,11.3608387 8,10.6906922 8,10 C8,9.30930775 8.02912147,8.63916129 8.08134222,8 L11.9186578,8 C11.9708785,8.63916129 12,9.30930775 12,10 C12,10.6906922 11.9708785,11.3608387 11.9186578,12 L8.08134222,12 L8.08134222,12 Z M8.33245212,14 C8.74471091,16.3918507 9.45909367,18 10,18 C10.5409063,18 11.2552891,16.3918507 11.6675479,14 L8.33245212,14 L8.33245212,14 Z M2.25203497,12 C2.08750601,11.3607602 2,10.6905991 2,10 C2,9.30940086 2.08750601,8.63923984 2.25203497,8 L6.06803651,8 C6.02298421,8.64627227 6,9.31512813 6,10 C6,10.6848719 6.02298421,11.3537277 6.06803651,12 L2.25203497,12 L2.25203497,12 Z M3.07025756,14 C4.00027261,15.6077187 5.47371379,16.8617486 7.23941494,17.5109236 C6.81922128,16.5491202 6.49875389,15.3524505 6.28904438,14 L3.07025756,14 L3.07025756,14 Z M16.9297424,6 C15.9997274,4.39228131 14.5262862,3.13825137 12.7605851,2.48907637 C13.1807787,3.45087984 13.5012461,4.64754949 13.7109556,6 L16.9297424,6 L16.9297424,6 Z M8.33245212,6 C8.74471091,3.60814928 9.45909367,2 10,2 C10.5409063,2 11.2552891,3.60814928 11.6675479,6 L8.33245212,6 L8.33245212,6 Z M3.07025756,6 C4.00027261,4.39228131 5.47371379,3.13825137 7.23941494,2.48907637 C6.81922128,3.45087984 6.49875389,4.64754949 6.28904438,6 L3.07025756,6 L3.07025756,6 Z M10,20 C15.5228475,20 20,15.5228475 20,10 C20,4.4771525 15.5228475,0 10,0 C4.4771525,0 0,4.4771525 0,10 C0,15.5228475 4.4771525,20 10,20 L10,20 Z" id="Combined-Shape"></path></g></g></svg>';

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
                ->resolveUsing(function ($collection, LanguageModel $language, $param) {
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
