<?php

namespace Marshmallow\Translatable\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Translation.
 *
 * @mixin Eloquent
 */
class Translation extends Model
{
    protected $guarded = [];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    // protected $with = ['language'];

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public static function getGroupsForLanguage($language)
    {
        return static::whereHas('language', function ($q) use ($language) {
            $q->where('language', $language);
        })->whereNotNull('group')
            ->where('group', 'not like', '%single')
            ->select('group')
            ->distinct()
            ->get();
    }
}
