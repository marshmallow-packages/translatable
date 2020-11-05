<?php

namespace Marshmallow\Translatable\Facades;

class Translatable extends \Illuminate\Support\Facades\Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return \Marshmallow\Translatable\Translatable::class;
    }
}
