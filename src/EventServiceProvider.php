<?php

namespace Marshmallow\Translatable;

use Marshmallow\Translatable\Events\TranslatableCreated;
use Marshmallow\Translatable\Listeners\UpdateMissingTranslations;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TranslatableCreated::class => [
            UpdateMissingTranslations::class,
        ]
    ];
}
