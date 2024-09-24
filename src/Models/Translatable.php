<?php

namespace Marshmallow\Translatable\Models;

use Illuminate\Database\Eloquent\Model;

class Translatable extends Model
{
    protected $fillable = [
        'source_field',
        'translated_value',
        'language_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updated(function (Model $resource) {
            $resource->translatable->updateMissingTranslations();
        });

        static::deleted(function (Model $resource) {
            $resource->translatable->missingTranslatable()->delete();
        });
    }


    public function language()
    {
        return $this->belongsTo(config('translatable.models.language'));
    }

    public function translatable()
    {
        return $this->morphTo();
    }
}
