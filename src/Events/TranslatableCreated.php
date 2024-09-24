<?php

namespace Marshmallow\Translatable\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Marshmallow\Translatable\Models\Translatable;

class TranslatableCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $translatable;

    public function __construct(Translatable $translatable)
    {
        $this->translatable = $translatable;
    }
}
