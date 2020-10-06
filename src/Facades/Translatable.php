<?php

namespace Marshmallow\Translatable\Facades;

/**
 */
class Translatable extends \Illuminate\Support\Facades\Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return \Marshmallow\Translatable\Translatable::class;
    }
}
