<?php

use Illuminate\Support\Facades\Route;
use Marshmallow\Translatable\Http\Controllers\SetLocaleController;
use Marshmallow\Translatable\Http\Controllers\SetTranslatableLocaleController;

Route::middleware(['web'])->scopeBindings()->group(
    function () {
        Route::get(
            'set-locale/{language:language}',
            SetLocaleController::class
        )->name('set-locale');

        Route::get(
            'set-translatable-locale/{language:language}',
            [SetTranslatableLocaleController::class, 'setLocale']
        )->missing(
            [SetTranslatableLocaleController::class, 'fallback']
        )->name('set-translatable-locale');
    }
);
