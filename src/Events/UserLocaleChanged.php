<?php

namespace Marshmallow\Translatable\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Marshmallow\Translatable\Models\Language;

class UserLocaleChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $language;

    public function __construct(Language $language)
    {
        $this->language = $language;
    }
}
