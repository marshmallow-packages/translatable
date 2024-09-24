<?php

namespace Marshmallow\Translatable\Listeners;

use Marshmallow\Translatable\Events\TranslatableCreated;

class UpdateMissingTranslations
{
    public function handle(TranslatableCreated $event)
    {
        if (config('translatable.missing_translations.active')) {
            $event->translatable->translatable->updateMissingTranslations();
        }
    }
}
