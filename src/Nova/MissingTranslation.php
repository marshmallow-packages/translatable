<?php

namespace Marshmallow\Translatable\Nova;

use App\Nova\Resource;
use Illuminate\Support\Arr;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Http\Requests\NovaRequest;
use Marshmallow\Translatable\Nova\Filters\MissingTranslationMorphFilter;

class MissingTranslation extends Resource
{
    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return __('Missing Translations');
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return __('Missing Translation');
    }

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'Marshmallow\Translatable\Models\MissingTranslation';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'source_field';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'missing_translatable_id',
        'missing_translatable_type',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request NovaRequest
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make(),
            MorphTo::make(__('Missing Translatable'), 'missingTranslatable')->types(
                Language::getTranslatableResources(),
            ),
            BelongsTo::make(__('Language'), 'language', Language::class),
            Text::make(__('Missing'), 'missing')->displayUsing(function ($value) {
                return Arr::join($value, ', ', ' and ');
            }),
        ];
    }

    public function filters(NovaRequest $request)
    {
        return [
            MissingTranslationMorphFilter::make(),
        ];
    }

    public function authorizedToUpdate(Request $request)
    {
        return false;
    }

    public function authorizedToDelete(Request $request)
    {
        return false;
    }

    public function authorizedToReplicate(Request $request)
    {
        return false;
    }

    public function authorizedToView(Request $request)
    {
        return false;
    }

    public static function authorizedToCreate(Request $request)
    {
        return false;
    }
}
