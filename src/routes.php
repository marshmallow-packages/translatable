<?php

use Illuminate\Support\Facades\Route;
use Marshmallow\Translatable\Http\Controllers\SetLocaleController;
use Marshmallow\Translatable\Http\Controllers\SetTranslatableLocaleController;

Route::middleware(['web'])
    ->get(
        'set-locale/{language:language}',
        SetLocaleController::class
    )->name('set-locale');

Route::middleware(['web'])
    ->get(
        'set-translatable-locale/{language:language}',
        SetTranslatableLocaleController::class
    )->name('set-translatable-locale');
