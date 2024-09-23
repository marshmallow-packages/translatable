<?php

use Illuminate\Support\Facades\Route;
use Marshmallow\Translatable\Http\Controllers\AutoTranslationController;

Route::get('settings', [AutoTranslationController::class, 'settings']);
Route::post('translate', [AutoTranslationController::class, 'translate']);
