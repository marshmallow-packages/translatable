<?php

namespace Marshmallow\Translatable\Nova\Filters;

use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;
use Marshmallow\Translatable\Models\Language;

class NoTranslationAvailableFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    public $name = 'Availability';

    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
    	switch ($value) {
    		case 'translated':
    			$query->whereNotNull('value')
    				  ->where('value', '!=', '');
    			break;

    		case 'not-translated':
    			$query->where(function ($query){
    				$query->whereNull('value')
    				  	  ->orWhere('value', '');
    			});
    			break;
    	}

        return $query;
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function options(Request $request)
    {
        return [
        	__('Translatated') => 'translated',
        	__('Not translatated') => 'not-translated',
        ];
    }
}
