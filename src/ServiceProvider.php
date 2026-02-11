<?php

namespace Marshmallow\Translatable;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Laravel\Nova\Nova;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Translators\TranslatorManager;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->registerRequestMacros();

        $this->loadMigrations();

        $this->publishConfiguration();

        $this->registerOptimizeHook();

        if (class_exists(Nova::class)) {
            $this->bootNova();
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/translatable.php', 'translatable');

        $this->registerTranslationLoader();

        $this->registerTranslatorManager();

        $this->registerCommands();
    }

    protected function registerTranslationLoader(): void
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new TranslationLoader();
        });
    }

    protected function registerTranslatorManager(): void
    {
        $this->app->singleton(TranslatorManager::class, function ($app) {
            return new TranslatorManager($app);
        });

        $this->app->alias(TranslatorManager::class, 'translatable.translator');
    }

    protected function registerRequestMacros(): void
    {
        Request::macro('setTranslatableLocale', function (Language $language) {
            Session::put('translatable-locale', $language->code);
        });

        Request::macro('getTranslatableLocale', function () {
            return Session::get('translatable-locale', App::currentLocale());
        });

        Request::macro('setUserLocale', function (Language $language) {
            Session::put('user-locale', $language->code);
            App::setLocale($language->code);
        });

        Request::macro('getUserLocale', function () {
            return Session::get('user-locale', App::currentLocale());
        });
    }

    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function publishConfiguration(): void
    {
        $this->publishes([
            __DIR__ . '/../config/translatable.php' => config_path('translatable.php'),
        ], 'translatable-config');
    }

    protected function registerOptimizeHook(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            if ($event->command !== 'optimize' || ! config('translatable.cache.enabled')) {
                return;
            }

            Artisan::call('translatable:cache');
        });
    }

    protected function bootNova(): void
    {
        Nova::serving(function () {
            Nova::script('translatable-field', __DIR__ . '/../dist/js/field.js');
            Nova::script('translatable-tool', __DIR__ . '/../dist/js/tool.js');
            Nova::style('translatable-field', __DIR__ . '/../dist/css/field.css');
        });

        Route::middleware(['nova'])
            ->prefix('nova-vendor/translatable')
            ->group(__DIR__ . '/../routes/nova.php');
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            Console\Commands\CacheCommand::class,
            Console\Commands\ClearCacheCommand::class,
            Console\Commands\ScanCommand::class,
            Console\Commands\ImportLaravelLangCommand::class,
            Console\Commands\ImportVendorCommand::class,
        ]);
    }
}
