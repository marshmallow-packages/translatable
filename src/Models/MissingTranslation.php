<?php

namespace Marshmallow\Translatable\Models;

use Illuminate\Database\Eloquent\Model;

class MissingTranslation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'missing' => 'array',
    ];

    public function language()
    {
        return $this->belongsTo(config('translatable.models.language'));
    }

    public function missingTranslatable()
    {
        return $this->morphTo();
    }
}
