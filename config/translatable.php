<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | The default language code for your application. This is used as the
    | source language for translations and as fallback when no translation
    | is available.
    |
    */

    'default_language' => env('TRANSLATABLE_DEFAULT_LANGUAGE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Enable file-based caching for translations. When enabled, translations
    | are stored in PHP files for fast retrieval. The cache is automatically
    | rebuilt when running `php artisan optimize`.
    |
    */

    'cache' => [
        'enabled' => env('TRANSLATABLE_CACHE_ENABLED', true),
        'path' => storage_path('framework/cache/translatable'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Translators
    |--------------------------------------------------------------------------
    |
    | Configure the AI translation providers. You can use DeepL, OpenAI,
    | or Anthropic for automatic translations.
    |
    */

    'translators' => [
        'default' => env('TRANSLATABLE_TRANSLATOR', 'deepl'),

        'deepl' => [
            'api_key' => env('DEEPL_API_KEY'),
            'api_url' => env('DEEPL_API_URL', 'https://api-free.deepl.com'),
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4'),
            'system_prompt' => 'You are a professional translator. Translate the text accurately while preserving any placeholders like :name, {variable}, or {{ blade }}. Only return the translated text, nothing else.',
        ],

        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-3-sonnet-20240229'),
            'system_prompt' => 'You are a professional translator. Translate the text accurately while preserving any placeholders like :name, {variable}, or {{ blade }}. Only return the translated text, nothing else.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel-Lang Presets
    |--------------------------------------------------------------------------
    |
    | Configuration for importing translations from Laravel-Lang packages.
    | Run `php artisan translatable:import {preset}` to import.
    |
    */

    'laravel_lang' => [
        'presets' => ['laravel', 'nova', 'filament', 'validation'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Settings
    |--------------------------------------------------------------------------
    |
    | Settings for importing translations from various sources.
    |
    */

    'import' => [
        'respect_locked' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Scanner Settings
    |--------------------------------------------------------------------------
    |
    | Configure paths and patterns for scanning translation keys in your
    | codebase. Run `php artisan translatable:scan` to find keys.
    |
    */

    'scan' => [
        'paths' => [
            app_path(),
            resource_path('views'),
        ],
        'patterns' => ['*.php', '*.blade.php', '*.vue'],
        'methods' => ['trans', '__', 'trans_choice', '@lang'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nova Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for Laravel Nova integration.
    |
    */

    'nova' => [
        'translation_matrix' => env('TRANSLATABLE_NOVA_MATRIX', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Flag Icons
    |--------------------------------------------------------------------------
    |
    | Configuration for language flag icons in Nova.
    |
    */

    'flag_icon' => [
        'height' => 40,
        'width' => 40,
    ],

    /*
    |--------------------------------------------------------------------------
    | Force Locale Query String
    |--------------------------------------------------------------------------
    |
    | A query string parameter that forces the locale to be set. Useful for
    | testing and deep linking to specific locales.
    |
    */

    'force_locale_query_string' => 'force_locale',
];
