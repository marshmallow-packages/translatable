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

    public function scopeOnlyGrouped($query)
    {
        return $query->whereNotNull('group')->whereIn('group', ['single']);
    }

    public function scopeOnlySingle($query)
    {
        return $query->whereNotNull('group')->whereNotIn('group', ['single']);
    }
}
