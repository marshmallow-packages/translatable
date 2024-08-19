<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Package driver
    |--------------------------------------------------------------------------
    |
    | The package supports different drivers for translation management.
    |
    | Supported: "file", "database"
    |
    */
    'driver' => 'database',

    /*
    |--------------------------------------------------------------------------
    | Translate resources in Nova
    |--------------------------------------------------------------------------
    |
    | When this is set to true, you will see the language selector when editing
    | a resource in Laravel Nova.
    |
    */
    'nova_translatable_fields' => true,

    /*
    |--------------------------------------------------------------------------
    | Language flag icons
    |--------------------------------------------------------------------------
    |
    | Update the ratios for the flag uploader here.
    |
    */
    'flag_icon' => [
        'height' => 40,
        'width' => 40,
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation methods
    |--------------------------------------------------------------------------
    |
    | Update this array to tell the package which methods it should look for
    | when finding missing translations.
    |
    */
    'translation_methods' => ['trans', '__'],

    /*
    |--------------------------------------------------------------------------
    | Scan paths
    |--------------------------------------------------------------------------
    |
    | Update this array to tell the package which directories to scan when
    | looking for missing translations.
    |
    */
    'scan_paths' => [
        app_path(),
        resource_path(),
    ],

    'models' => [
        'language' => \Marshmallow\Translatable\Models\Language::class,
        'translation' => \Marshmallow\Translatable\Models\Translation::class,
        'translatable' => \Marshmallow\Translatable\Models\Translatable::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Force locale
    |--------------------------------------------------------------------------
    |
    | This is a query string parameter which will force the locale to be set
    | to the given locale. This is useful for testing purposes and when
    | you want to deep link to a specific locale.
    |
    */
    'force_locale_query_string' => 'force_locale',

    'deepl' => [
        'api_path' => env('TRANSLATABLE_DEEPL_API_PATH', 'https://api-free.deepl.com'),
        'api_key' => env('TRANSLATABLE_DEEPL_API_KEY'),
    ],
];
