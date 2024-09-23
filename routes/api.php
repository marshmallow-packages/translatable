<?php

use Illuminate\Support\Facades\Route;
use Marshmallow\Translatable\Http\Controllers\LanguageController;
use Marshmallow\Translatable\Http\Controllers\LanguageTranslationController;

Route::get('languages', LanguageController::class . '@index')
    ->name('nova.languages.index');

Route::get('languages/{language}/translations', LanguageTranslationController::class . '@index')
    ->name('nova.languages.translations.index');

Route::put('languages/{language}/translations', LanguageTranslationController::class . '@update')
    ->name('nova.languages.translations.update');
