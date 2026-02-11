<?php

use Illuminate\Support\Facades\Route;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\TranslatableConfig;

/*
|--------------------------------------------------------------------------
| Nova Vendor Routes
|--------------------------------------------------------------------------
|
| Routes for the Translatable Nova integration.
|
*/

Route::get('/languages', function () {
    return [
        'languages' => Language::active()
            ->ordered()
            ->get()
            ->map(fn ($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'icon' => $lang->getIconUrl(),
            ]),
        'defaultLanguage' => TranslatableConfig::getDefaultLanguage(),
    ];
});

/*
|--------------------------------------------------------------------------
| Translation Matrix Routes
|--------------------------------------------------------------------------
*/

use Marshmallow\Translatable\Http\Controllers\TranslationMatrixController;

Route::prefix('translation-matrix')->group(function () {
    Route::get('/', [TranslationMatrixController::class, 'index']);
    Route::get('/grouped', [TranslationMatrixController::class, 'grouped']);
    Route::post('/', [TranslationMatrixController::class, 'store']);
    Route::put('/{id}', [TranslationMatrixController::class, 'update']);
    Route::delete('/{id}', [TranslationMatrixController::class, 'destroy']);
    Route::post('/{id}/lock', [TranslationMatrixController::class, 'lock']);
    Route::post('/{id}/unlock', [TranslationMatrixController::class, 'unlock']);
    Route::post('/translate', [TranslationMatrixController::class, 'translate']);
    Route::post('/translate-batch', [TranslationMatrixController::class, 'translateBatch']);
    Route::get('/translators', [TranslationMatrixController::class, 'getTranslators']);
});
