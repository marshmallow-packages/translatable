<?php

namespace Marshmallow\Translatable\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;
use Marshmallow\Translatable\Cache\TranslatableCache;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Models\Translatable;
use Marshmallow\Translatable\TranslatableConfig;

trait Translatable
{
    protected ?array $loadedTranslations = null;

    public static function bootTranslatable(): void
    {
        if (TranslatableConfig::shouldAutoLoad()) {
            static::addGlobalScope('translations', function ($query) {
                $query->with('translations');
            });
        }

        static::deleting(function (Model $model) {
            $model->translations()->delete();

            if (TranslatableConfig::isCacheEnabled()) {
                TranslatableCache::clearModel(static::class);
            }
        });
    }

    abstract public function translatableColumns(): array;

    public function translations(): MorphMany
    {
        return $this->morphMany(Translatable::class, 'translatable');
    }

    public function scopeWithTranslations($query): void
    {
        $query->with('translations');
    }

    public function scopeWithoutTranslations($query): void
    {
        $query->withoutGlobalScope('translations');
    }

    public function isTranslatableAttribute(string $key): bool
    {
        return in_array($key, $this->translatableColumns());
    }

    public function getAttributeValue($key): mixed
    {
        if (! $this->isTranslatableAttribute($key)) {
            return parent::getAttributeValue($key);
        }

        if ($this->isDefaultLocale()) {
            return parent::getAttributeValue($key);
        }

        $translation = $this->getTranslation($key);

        if ($translation !== null) {
            return $this->transformModelValue($key, $translation);
        }

        return parent::getAttributeValue($key);
    }

    public function getTranslation(string $field, ?string $locale = null): mixed
    {
        $locale = $locale ?? App::getLocale();
        $language = $this->resolveLanguage($locale);

        if (! $language) {
            return null;
        }

        if (TranslatableConfig::isCacheEnabled()) {
            $cached = TranslatableCache::get(
                static::class,
                $this->getKey(),
                $field,
                $language->id
            );

            if ($cached !== null) {
                return $this->decodeValue($cached);
            }
        }

        $this->loadTranslationsIfNeeded();

        $translation = $this->loadedTranslations[$field][$language->id] ?? null;

        return $translation ? $this->decodeValue($translation) : null;
    }

    public function setTranslation(string $locale, string|array $field, mixed $value = null): void
    {
        $language = $this->resolveLanguage($locale);

        if (! $language) {
            return;
        }

        if (is_array($field)) {
            foreach ($field as $fieldName => $fieldValue) {
                $this->storeTranslation($language, $fieldName, $fieldValue);
            }
        } else {
            $this->storeTranslation($language, $field, $value);
        }

        $this->loadedTranslations = null;

        if (TranslatableConfig::isCacheEnabled()) {
            TranslatableCache::clearModel(static::class);
        }
    }

    public function hasTranslation(string $field, ?string $locale = null): bool
    {
        return $this->getTranslation($field, $locale) !== null;
    }

    public function getTranslationsForField(string $field): array
    {
        $this->loadTranslationsIfNeeded();

        $translations = [];

        foreach ($this->loadedTranslations[$field] ?? [] as $languageId => $value) {
            $language = Language::find($languageId);

            if ($language) {
                $translations[$language->code] = $this->decodeValue($value);
            }
        }

        return $translations;
    }

    public function getAllTranslations(): array
    {
        $this->loadTranslationsIfNeeded();

        $translations = [];

        foreach ($this->loadedTranslations as $field => $values) {
            $translations[$field] = [];

            foreach ($values as $languageId => $value) {
                $language = Language::find($languageId);

                if ($language) {
                    $translations[$field][$language->code] = $this->decodeValue($value);
                }
            }
        }

        return $translations;
    }

    protected function storeTranslation(Language $language, string $field, mixed $value): void
    {
        if (! $this->isTranslatableAttribute($field)) {
            return;
        }

        $encodedValue = $this->encodeValue($value);

        $this->translations()->updateOrCreate(
            [
                'field' => $field,
                'language_id' => $language->id,
            ],
            [
                'value' => $encodedValue,
                'source' => 'manual',
            ]
        );
    }

    protected function loadTranslationsIfNeeded(): void
    {
        if ($this->loadedTranslations !== null) {
            return;
        }

        $this->loadedTranslations = [];

        if ($this->relationLoaded('translations')) {
            $translations = $this->translations;
        } else {
            $translations = $this->translations()->get();
        }

        foreach ($translations as $translation) {
            $field = $translation->field;
            $languageId = $translation->language_id;

            if (! isset($this->loadedTranslations[$field])) {
                $this->loadedTranslations[$field] = [];
            }

            $this->loadedTranslations[$field][$languageId] = $translation->value;
        }
    }

    protected function resolveLanguage(string $locale): ?Language
    {
        return Language::where('code', $locale)->first();
    }

    protected function isDefaultLocale(): bool
    {
        return App::getLocale() === TranslatableConfig::getDefaultLanguage();
    }

    protected function encodeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    protected function decodeValue(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return $value;
    }
}
