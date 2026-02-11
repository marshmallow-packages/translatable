<?php

namespace Marshmallow\Translatable\Nova\Tools;

use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class TranslationMatrix extends Tool
{
    public function boot(): void
    {
        Nova::script('translation-matrix', __DIR__ . '/../../../dist/js/tool.js');
        Nova::style('translation-matrix', __DIR__ . '/../../../dist/css/tool.css');
    }

    public function menu(): mixed
    {
        return MenuSection::make('Translation Matrix')
            ->path('/translation-matrix')
            ->icon('translate');
    }
}
