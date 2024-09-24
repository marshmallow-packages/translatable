<?php

namespace Marshmallow\Translatable\Nova\Filters;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Filters\Filter;
use Marshmallow\Translatable\Models\MissingTranslation;

class MissingTranslationMorphFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * Apply the filter to the given query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed                                 $value
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        if ($value) {
            $query->where('missing_translatable_type', $value);
        }

        return $query;
    }

    /**
     * Get the filter's available options.
     *
     * @return array
     */
    public function options(NovaRequest $request)
    {
        return MissingTranslation::query()
            ->groupBy('missing_translatable_type')
            ->get()
            ->mapWithKeys(function ($item) {
                $label = explode('\\', $item->missing_translatable_type);
                $label = end($label);
                return [
                    $label => $item->missing_translatable_type,
                ];
            })
            ->toArray();
    }
}
