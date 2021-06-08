<?php

namespace Marshmallow\Translatable\Facades;

class TranslatableTabs extends \Illuminate\Support\Facades\Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return \Marshmallow\Translatable\TranslatableTabs::class;
    }
}
