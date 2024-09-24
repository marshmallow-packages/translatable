<?php

namespace Marshmallow\Translatable\Nova\Metric;

use App\Nova\Blog;
use App\Nova\Page;
use App\Facades\VDH;
use App\Models\Language;
use Laravel\Nova\Resource;
use BadMethodCallException;
use Illuminate\Support\Arr;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Metrics\Table;
use Laravel\Nova\Metrics\MetricTableRow;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Nova\Filters\MissingTranslationFilter;
use Marshmallow\Translatable\Models\MissingTranslation;
use Marshmallow\Translatable\Models\Language as LanguageModel;
use Marshmallow\Translatable\Nova\Filters\MissingTranslationMorphFilter;
use Marshmallow\Translatable\Nova\MissingTranslation as NovaMissingTranslation;

class MissingTranslations extends Table
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        $checklist_items = [];
        $checklist_items = $this->getResourceTranslationChecklist($checklist_items);
        return $checklist_items;
    }

    protected function getResourceTranslationChecklist($checklist_items): array
    {
        $checklist_items = [];
        $models_and_resources = LanguageModel::getTranslatableModelsWithResources();

        MissingTranslation::query()
            ->groupBy('missing_translatable_type')
            ->each(function ($item) use (&$checklist_items, $models_and_resources) {

                $count = MissingTranslation::query()
                    ->where('missing_translatable_type', $item->missing_translatable_type)
                    ->groupBy('missing_translatable_id')
                    ->get()
                    ->count();

                $nova_resource = Arr::get($models_and_resources, $item->missing_translatable_type);

                $filters = [
                    [
                        MissingTranslationMorphFilter::class => $item->missing_translatable_type
                    ]
                ];

                /** @var Resource $missing_nova_resource */
                $missing_nova_resource = NovaMissingTranslation::class;
                $resource_uri_key = $missing_nova_resource::uriKey();
                $filter_string = base64_encode(json_encode($filters));
                $url = "/resources/{$resource_uri_key}?{$resource_uri_key}_filter={$filter_string}";

                $checklist_links[] = MenuItem::link(
                    __('View the list'),
                    $url
                );

                $checklist_items[] = MetricTableRow::make()
                    ->title(
                        __(':resource translations are missing', [
                            'resource' => $nova_resource::label(),
                        ])
                    )
                    ->subtitle(__(':count items have missing translations', [
                        'count' => $count,
                    ]))
                    ->actions(function () use ($checklist_links) {
                        return $checklist_links;
                    });
            });

        return $checklist_items;
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     *
     * @return \DateTimeInterface|\DateInterval|float|int|null
     */
    // public function cacheFor()
    // {
    //     return now()->addMinutes(60);
    // }
}
