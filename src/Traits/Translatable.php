<?php

namespace Marshmallow\Translatable\Traits;

use Error;
use Laravel\Nova\Resource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Http\Requests\NovaRequest;
use Marshmallow\HelperFunctions\Facades\URL;
use Marshmallow\Translatable\Fields\LanguageToggler;
use Marshmallow\Translatable\Events\TranslatableCreated;
use Marshmallow\Translatable\Facades\Translatable as TranslatableFacade;

trait Translatable
{
    protected $use_translator = true;

    protected $protected_from_translations = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static function bootTranslatable()
    {
        static::creating(function (Model $resource) {
            /*
             * Creating should always be done in the original
             * language and our nova package will not make it
             * possible to insert translations directly. You
             * will need to create the resource in the app.locale
             * first.
             */
        });

        static::created(function (Model $resource) {
            DB::afterCommit(
                function () use ($resource) {
                    $resource->updateMissingTranslations();
                }
            );
        });

        static::updating(function (Model $resource) {
            /*
        	 * If the current translatable locale is different
        	 * from the original, then we are creating or updating
        	 * translations.
        	 */
            if ($resource->translatorActive() && $resource->weAreTranslating()) {
                /*
                * Create the translations.
                */
                $resource->setTranslation(
                    Request::getTranslatableLocale(),
                    $resource->getDirty()
                );

                /*
        		 * Reset this resource to its original values
                 * because the should nog be stored in the
                 * resource itself.
        		 */
                $resource->resetToOriginal();
                $resource->timestamps = false;
            }
        });

        static::updated(function (Model $resource) {
            $resource->updateMissingTranslations();
        });

        static::deleting(function (Model $resource) {
            /*
             * Delete the existing translations.
             */
            $resource->translatable()->delete();
        });

        static::deleted(function (Model $resource) {
            $resource->missingTranslatable()->delete();
        });
    }

    public function dontTranslate()
    {
        $this->use_translator = false;
        return $this;
    }

    public function doTranslate()
    {
        $this->use_translator = true;
        return $this;
    }

    public function translatorActive()
    {
        return $this->use_translator === true;
    }

    public function weAreTranslating()
    {
        if ($this->force_translating_status) {
            return true;
        }

        if (method_exists($this, 'translatableFieldsEnabled') && !$this->translatableFieldsEnabled()) {
            return false;
        }

        if (Request::hasMacro('getTranslatableLocale')) {
            $default_locale = config('app.default_locale') ?? config('app.locale');
            return Request::getTranslatableLocale() !== $default_locale;
        }

        return false;
    }

    public function weAreNotTranslating()
    {
        return !$this->weAreTranslating();
    }

    public function resetToOriginal(): self
    {
        foreach ($this->getDirty() as $field => $value) {
            $this->{$field} = $this->getOriginal($field);
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
                $translatable->updateQuietly([
                    'translated_value' => $translated_value,
                ]);
            } else {
                $this::withoutEvents(function () use ($source_field, $translated_value, $language) {
                    $new_translatable = $this->translatable()->create([
                        'source_field' => $source_field,
                        'translated_value' => $translated_value,
                        'language_id' => $language->id,
                    ]);

                    $new_translatable->saveQuietly();

                    event(new TranslatableCreated($new_translatable));
                });
            }
        }
    }

    /**
     * Get the attribute value. Only if this column is
     * indeed translatable.
     */
    public function getAttributeValue($key)
    {
        if ($this->weAreNotTranslating() || !$this->isTranslatableAttribute($key)) {
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
    public function getTranslation($source_field, $language)
    {
        $language = $this->getLanguageByTranslationParameter($language);
        if ($translation = $this->getExistingTranslation($source_field, $language)) {
            $translation = $translation->translated_value;

            /*
             * Make sure we apply casts and mutators.
             */
            return $this->transformModelValue($source_field, $translation);
        }

        return parent::getAttributeValue($source_field);
    }

    public static function getTranslatableResources(?string $path = null, array $ignore = []): array
    {
        $path = $path ?? app_path('Nova');
        return self::getTranslatableClasses(
            path: $path,
            ignore: $ignore,
            uses: TranslatableFields::class,
        );
    }

    public static function getTranslatableResourcesWithModels(?string $path = null, array $ignore = []): array
    {
        $path = $path ?? app_path('Nova');
        return self::getTranslatableClasses(
            path: $path,
            ignore: $ignore,
            uses: TranslatableFields::class,
            add_callback: function ($filename) {
                return [$filename, $filename::$model];
            }
        );
    }

    public static function getTranslatableModelsWithResources()
    {
        return array_flip(self::getTranslatableResourcesWithModels());
    }

    public static function getTranslatableModels(?string $path = null, array $ignore = []): array
    {
        $path = $path ?? app_path('Models');
        return self::getTranslatableClasses(
            path: $path,
            ignore: $ignore,
            uses: Translatable::class,
        );
    }

    public static function getTranslatableClasses(?string $path = null, array $ignore = [], ?string $uses = null, ?callable $add_callback = null): array
    {
        $run_again_method = Arr::get(debug_backtrace(), '1.function');

        $models = [];
        foreach (glob($path . '/*') as $file_path) {
            if (is_dir($file_path)) {
                $models = array_merge($models, self::{$run_again_method}($file_path, $ignore, $uses, $add_callback));
            } else {
                $filename = Str::of($file_path)
                    ->remove(app_path())
                    ->remove('.php')
                    ->prepend('App')
                    ->replace('/', '\\')
                    ->toString();

                if (!$uses) {
                    $models[] = $filename;
                } else {
                    if (in_array($uses, class_uses_recursive($filename))) {
                        if ($add_callback) {
                            [$key, $value] = $add_callback($filename);
                            $models[$key] = $value;
                        } else {
                            $models[] = $filename;
                        }
                    }
                }
            }
        }

        return collect($models)
            ->reject(fn($model) => in_array($model, $ignore))
            ->toArray();
    }

    public function updateMissingTranslations()
    {
        if (!config('translatable.missing_translations.active')) {
            return;
        }

        $this->missingTranslatable()->delete();

        $models = self::getTranslatableModelsWithResources();
        $nova_resources = Arr::get($models, get_class($this));
        $nova_request = resolve(NovaRequest::class);
        $nova_request->merge([
            'editMode' => 'update',
        ]);

        request()->merge([
            'editMode' => 'update',
        ]);

        try {
            $fields = (new $nova_resources($this))
                ->forceTranslating()
                ->fields($nova_request);
        } catch (Error) {
            return;
        }

        $translatable_columns = [];
        collect($fields)
            ->reject(fn($field) => $field instanceof LanguageToggler)
            ->reject(fn($field) => in_array(RelatableField::class, class_implements($field)))
            ->each(function ($field) use (&$translatable_columns) {
                if (get_class($field) == 'Laravel\Nova\Tabs\TabsGroup') {
                    collect($field->data)
                        ->reject(fn($field) => in_array(RelatableField::class, class_implements($field)))
                        ->each(function ($tab_field) use (&$translatable_columns) {
                            $translatable_columns[] = $tab_field->attribute;
                        });
                } else {
                    $translatable_columns[] = $field->attribute;
                }
            });

        $languages = config('translatable.models.language')::query()
            ->active()
            ->ignoreDefault()
            ->get();

        $languages->each(function ($language) use ($translatable_columns) {
            $missing_data = [];
            collect($translatable_columns)
                ->each(function ($column) use (&$missing_data, $language) {
                    $translated = $this->translatable
                        ->where('source_field', $column)
                        ->where('language_id', $language->id)
                        ->first();

                    if (!$translated) {
                        $missing_data[] = $column;
                    }
                });


            if (!empty($missing_data)) {
                $this->missingTranslatable()->create([
                    'missing' => $missing_data,
                    'language_id' => $language->id,
                ]);
            }
        });
    }

    /**
     * Set up the relationship.
     */
    public function translatable()
    {
        return $this->morphMany(config('translatable.models.translatable'), 'translatable');
    }

    public function missingTranslatable()
    {
        return $this->morphMany(config('translatable.models.missingTranslation'), 'missing_translatable');
    }

    /**
     * Get a language model. This method has been created so we
     * can make it possible to get the language by more than just
     * the language column.
     */
    protected function getLanguageByTranslationParameter($language)
    {
        return TranslatableFacade::getLanguageByTranslationParameter($language);
    }

    /**
     * Check if this column is already translated.
     */
    protected function getExistingTranslation($source_field, $language): ?Model
    {
        if (!isset($this->getAttributes()[$this->primaryKey])) {
            return null;
        }

        if ($this->translatable) {
            return $this->translatable
                ->where('source_field', $source_field)
                ->where('language_id', $language->id)
                ->first();
        }

        return config('translatable.models.translatable')::where('translatable_type', get_class($this))
            ->where('translatable_id', $this->getAttributes()[$this->primaryKey])
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

    public function notTranslateColumns(): array
    {
        return [];
    }

    public function translatableColumns(): array
    {
        return [];
    }

    /**
     * This is a traits used on Eloquent models and on
     * Nova resources. We check here which one we have.
     */
    public function getNotTranslateColumns()
    {
        if (class_exists(Resource::class) && $this instanceof Resource) {
            $nova_resource = $this;
            $resource = $nova_resource::newModel();
            return $resource->notTranslateColumns();
        }

        return $this->notTranslateColumns();
    }

    /**
     * This is a traits used on Eloquent models and on
     * Nova resources. We check here which one we have.
     */
    public function getTranslatableColumns()
    {
        if (class_exists(Resource::class) && $this instanceof Resource) {
            $nova_resource = $this;
            $resource = $nova_resource::newModel();
            return $resource->translatableColumns();
        }

        return $this->translatableColumns();
    }

    /**
     * Build an array with all the columns for this model that are translatable.
     */
    public function getTranslatableAttributes(): array
    {
        $translatable_columns = array_keys($this->getAttributes());
        if (!empty($this->getTranslatableColumns())) {
            $translatable_columns = $this->getTranslatableColumns();
        }

        foreach ($this->getNotTranslateColumns() as $ignore_column) {
            $key = array_search($ignore_column, $translatable_columns);
            if (false === $key) {
                continue;
            }
            unset($translatable_columns[$key]);
        }
        if (isset($this->protected_from_translations) && is_array($this->protected_from_translations)) {
            foreach ($this->protected_from_translations as $protected_column) {
                $key = array_search($protected_column, $translatable_columns);
                if (false === $key) {
                    continue;
                }
                unset($translatable_columns[$key]);
            }
        }

        return $translatable_columns;
    }

    /**
     * Convert the input to an array so both methods below are possible
     * $page->setTranslation('en', 'name', 'Products');.
     *
     * $page->setTranslation('en', [
     *     'name' => 'Products',
     * ]);
     */
    protected function convertTranslationInputToArray($source_field, $translated_value = null): array
    {
        if ($source_field instanceof Model) {
            return $source_field->toArray();
        }

        if (is_array($source_field)) {
            return $source_field;
        }

        return [
            $source_field => $translated_value,
        ];
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request NovaRequest
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        if ($this->weAreNotTranslating() || ($request->has('editMode') && 'create' == $request->editMode)) {
            return $this->addTranslationToggleField(
                $this->translatableFields($request),
                $request
            );
        }

        $fields = $this->translatableFields($request);
        foreach ($fields as $key => $field) {
            if (isset($field->attribute) && !$this->isTranslatableAttribute($field->attribute)) {
                unset($fields[$key]);
            }
        }

        return $this->addTranslationToggleField(
            $fields,
            $request
        );
    }

    /**
     * LEGACY FROM MULTI-LANGUAGE PACKAGE.
     */
    public function localeRoute($language = null)
    {
        return URL::buildFromArray(
            $this->getRouteParts($language)
        );
    }

    protected function getRouteParts($language = null)
    {
        return array_filter([
            $this->getLocale($language),
            $this->routePrefix(),
            $this->getModelUrl($language),
        ]);
    }

    protected function getModelUrlField()
    {
        return $this->getRouteKeyName();
    }

    protected function getModelUrl($language = null)
    {
        $url_column = $this->getModelUrlField();
        if ($language) {
            return $this->getTranslation($url_column, $language->code);
        }

        return $this->{$url_column};
    }

    protected function routePrefix()
    {
        return '';
    }

    /**
     * STATICS
     */

    /**
     * Get a resource by its translated slug.
     */
    public static function getByTranslatedSlug($slug, $slug_column = 'slug')
    {
        $translation = config('translatable.models.translatable')::query()
            ->where('source_field', $slug_column)
            ->where('translated_value', $slug)
            ->where('translatable_type', self::class)
            ->with('translatable')
            ->first();

        if ($translation) {
            return $translation->translatable;
        }

        return self::where($slug_column, $slug)->first();
    }

    /**
     * LEGACY FROM MULTI-LANGUAGE PACKAGE.
     */

    /**
     * Get the current locale.
     */
    public function getLocale($language = null): string
    {
        if ($language) {
            return $language->language;
        }

        if (URL::isNova(request())) {
            return request()->getTranslatableLocale();
        }

        return request()->getUserLocale();
    }
}
