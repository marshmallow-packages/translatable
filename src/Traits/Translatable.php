<?php

namespace Marshmallow\Translatable\Traits;

use Request;
use Illuminate\Database\Eloquent\Model;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Models\Translatable as TranslatableModel;

trait Translatable
{
	// public $translatable = [];

	// public $not_translatable = [];

	protected $protected_columns = [
		'id',
		'created_at',
		'updated_at',
		'deleted_at',
	];

	public static function bootTranslatable()
    {
    	static::creating(function (Model $resource) {
	        /**
	         * Creating should always be done in the original
	         * lanuage and our nova package will not make it
	         * possible to insert translations directly. You
	         * will need to create the resource in the app.locale
	         * first.
	         */
	    });

        static::updating(function (Model $resource) {
        	/**
        	 * If the current translatable locale is different
        	 * from the original, then we are creating or updating
        	 * translations.
        	 */
        	if (Request::translatableLocale() !== config('app.locale')) {

        		/**
        		 * Create the translations.
        		 */
        		$resource->setTranslation(
        			Request::translatableLocale(),
        			$resource->getDirty()
        		);

        		/**
        		 * Reset this resource to its original values
        		 * because the should nog be stored in the
        		 * resource itself.
        		 */
        		$resource->resetToOriginal();
        	}
	    });

	    static::deleting(function (Model $resource) {
	        /**
	         * Delete the existing translations.
	         */
	    });
    }

    public function resetToOriginal(): self
    {
    	if (!$this->isDirty()) {
    		return $this;
    	}
    	foreach ($this->getDirty() as $column => $new_value) {
    		$this->{$column} = $this->getOriginal($column);
    	}
    	return $this;
    }

	/**
	 * Store the translation in the database.
	 */
	public function setTranslation($language, $source_field, $translated_value = null)
	{
		$language = $this->getLanguageByTranslationParameter($language);
		$source_fields = $this->convertTranslationInputToArray($source_field, $translated_value);

		foreach ($source_fields as $source_field => $translated_value) {
			if (!$this->isTranslatableAttribute($source_field)) {
				continue;
			}
			if ($translatable = $this->getExistingTranslation($source_field, $language)) {
				$translatable->update([
					'translated_value' => $translated_value,
				]);
			} else {
				$this->translatable()->create([
					'source_field' => $source_field,
					'translated_value' => $translated_value,
					'language_id' => $language->id,
				]);
			}
		}
	}

	/**
	 * Get the attribute value. Only if this column is
	 * indeed translatable.
	 */
	public function getAttributeValue($key)
    {
        if (!$this->isTranslatableAttribute($key)) {
            return parent::getAttributeValue($key);
        }
        return $this->getTranslation($key, $this->getLocale());
    }


    /**
     * Get the translated value from the database if it exists,
     * if not, we return the default value from the model in the
     * original language. We do this so we never return an empty
     * string (unless the value in the database is empty of course).
     */
	public function getTranslation($source_field, $language): ? string
	{
		$language = $this->getLanguageByTranslationParameter($language);
		if ($translation = $this->getExistingTranslation($source_field, $language)) {
			return $translation->translated_value;
		}

		return $this->getAttributes()[$source_field];
	}


	/**
	 * Set up the relationship
	 */
	public function translatable()
    {
        return $this->morphMany(TranslatableModel::class, 'translatable');
    }

    /**
     * Get a language model. This method has been created so we
     * can make it possible to get the language by more than just
     * the language column.
     */
    protected function getLanguageByTranslationParameter($language): Model
    {
    	return Language::where('language', $language)->firstOrFail();
    }

    /**
     * Check if this column is already translated.
     */
    protected function getExistingTranslation($source_field, Language $language): ? Model
	{
		return $this->translatable()
					->where('source_field', $source_field)
					->where('language_id', $language->id)
					->first();
	}

	/**
	 * Check if this column is indeed translatable.
	 */
	public function isTranslatableAttribute(string $key): bool
    {
        return in_array($key, $this->getTranslatableAttributes());
    }

    /**
     * Build an array with all the columns for this model that are translatable.
     */
    public function getTranslatableAttributes(): array
    {
    	$translatable_columns = array_keys($this->getAttributes());

    	if (isset($this->translatable) && is_array($this->translatable)) {
    		$translatable_columns = $this->translatable;
    	}
    	if (isset($this->not_translatable) && is_array($this->not_translatable)) {
    		foreach ($this->not_translatable as $ignore_column) {
    			$key = array_search($ignore_column, $translatable_columns);
    			unset($translatable_columns[$key]);
    		}
    	}
    	if (isset($this->protected_columns) && is_array($this->protected_columns)) {
    		foreach ($this->protected_columns as $protected_column) {
    			$key = array_search($protected_column, $translatable_columns);
    			unset($translatable_columns[$key]);
    		}
    	}

    	return $translatable_columns;
    }

    /**
     * Convert the input to an array so both methods below are possible
     * $page->setTranslation('nl', 'name', 'Artikelen');
     *
     * $page->setTranslation('nl', [
     * 	'name' => 'Artikelen',
     * ]);
     */
    protected function convertTranslationInputToArray($source_field, $translated_value = null): array
	{
		if (is_array($source_field)) {
			return $source_field;
		}

		return [
			$source_field => $translated_value
		];
	}

	/**
	 * Get the current locale
	 */
	public function getLocale(): string
    {
        return config('app.locale');
    }
}
