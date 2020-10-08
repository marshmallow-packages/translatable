<?php

namespace Marshmallow\Translatable\Models;

use Illuminate\Database\Eloquent\Model;
use Marshmallow\Translatable\Models\Language;

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
}
