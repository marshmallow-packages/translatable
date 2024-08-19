<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Console\Command;
use Marshmallow\Translatable\Scanner\Drivers\Translation;

class BaseCommand extends Command
{
    protected $translation;

    public function __construct(Translation $translation)
    {
        parent::__construct();
        $this->translation = $translation;
    }
}
