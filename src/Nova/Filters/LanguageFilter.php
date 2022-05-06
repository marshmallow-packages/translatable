<?php

namespace Marshmallow\Translatable\Nova\Filters;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Filters\Filter;

class LanguageFilter extends Filter
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
            $query->where('language_id', $value);
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
        return config('translatable.models.language')::get()->pluck('id', 'name')->toArray();
    }
}
