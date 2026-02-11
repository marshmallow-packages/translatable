<?php

namespace Marshmallow\Translatable;

class TranslatableConfig
{
    protected static bool $autoLoad = false;

    public static function autoLoad(): void
    {
        static::$autoLoad = true;
    }

    public static function shouldAutoLoad(): bool
    {
        return static::$autoLoad;
    }

    public static function getDefaultLanguage(): string
    {
        return config('translatable.default_language', 'en');
    }

    public static function getCachePath(): string
    {
        return config('translatable.cache.path', storage_path('framework/cache/translatable'));
    }

    public static function isCacheEnabled(): bool
    {
        return config('translatable.cache.enabled', true);
    }

    public static function getDefaultTranslator(): string
    {
        return config('translators.default', 'deepl');
    }

    public static function shouldRespectLocked(): bool
    {
        return config('translatable.import.respect_locked', true);
    }
}
