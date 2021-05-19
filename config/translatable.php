<?php

return [
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
];
