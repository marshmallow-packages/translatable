<?php

namespace Marshmallow\Translatable\Models;

use Illuminate\Database\Eloquent\Model;

class Translatable extends Model
{
	protected $fillable = [
		'source_field', 'translated_value', 'language_id',
	];

	public function translatable()
    {
        return $this->morphTo();
    }
}
