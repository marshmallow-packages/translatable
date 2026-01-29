<?php

namespace Marshmallow\Translatable;

use Laravel\Nova\Nova;
use Illuminate\Support\Facades\App;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Marshmallow\HelperFunctions\Facades\URL;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Scanner\Scanner;
use Marshmallow\Translatable\Events\UserLocaleChanged;
use Marshmallow\Translatable\Scanner\TranslationManager;
use Marshmallow\Translatable\Scanner\Drivers\Translation;
use Marshmallow\Translatable\Console\Commands\PresetCommand;
use Marshmallow\Translatable\Console\Commands\InstallCommand;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Marshmallow\Translatable\Console\Commands\AddLanguageCommand;
use Marshmallow\Translatable\Console\Commands\ListLanguagesCommand;
use Marshmallow\Translatable\Console\Commands\GeneratePresetCommand;
use Marshmallow\Translatable\Console\Commands\TranslationCacheCommand;
use Marshmallow\Translatable\Console\Commands\TranslationClearCommand;
use Marshmallow\Translatable\Console\Commands\AddTranslationKeyCommand;
use Marshmallow\Translatable\Console\Commands\TranslatableCacheCommand;
use Marshmallow\Translatable\Console\Commands\TranslatableClearCommand;
use Marshmallow\Translatable\Console\Commands\IndexMissingTranslatables;
use Marshmallow\Translatable\Console\Commands\ListMissingTranslationKeys;
use Marshmallow\Translatable\Console\Commands\DuplicateTranslationsCommand;
use Marshmallow\Translatable\Console\Commands\SynchroniseTranslationsCommand;
use Marshmallow\Translatable\Console\Commands\FixTranslatedPlaceholdersCommand;
use Marshmallow\Translatable\Console\Commands\SynchroniseMissingTranslationKeys;
use Marshmallow\Translatable\Console\Commands\SynchroniseTranslationsFromToCommand;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        Request::macro('setTranslatableLocale', function ($language): void {
            Session::put('translatable-locale', $language->language);
            Cache::put('translatable-locale', $language->language);
        });

        Request::macro('getTranslatableLocale', function () {
            static $cachedLocale = null;

            if ($cachedLocale !== null) {
                return $cachedLocale;
            }

            $session_key = (URL::isNova(request())) ? 'translatable-locale' : 'user-locale';
            $app_locale = App::currentLocale();

            if (Session::has($session_key)) {
                $cachedLocale = Session::get($session_key);

                return $cachedLocale;
            }
            Session::put($session_key, $app_locale);

            if (Cache::has($session_key)) {
                $cachedLocale = Cache::get($session_key);

                return $cachedLocale;
            }
            Cache::put($session_key, $app_locale);

            $cachedLocale = $app_locale;

            return $cachedLocale;
        });

        Request::macro('setUserLocale', function (Language $language): void {
            Session::put('user-locale', $language->language);
            App::setLocale($language->language);
            event(new UserLocaleChanged($language));
        });

        Request::macro('getUserLocale', function () {
            static $cachedUserLocale = null;

            if ($cachedUserLocale !== null) {
                return $cachedUserLocale;
            }

            $locale_key = 'user-locale';

            if (Session::has($locale_key)) {
                $cachedUserLocale = Session::get($locale_key);
            } else {
                $cachedUserLocale = App::currentLocale();
            }

            return $cachedUserLocale;
        });

        Nova::serving(function (): void {
            Nova::script('language-toggle-field', __DIR__ . '/../dist/js/field.js');
            Nova::style('language-toggle-field', __DIR__ . '/../dist/css/field.css');
        });

        Route::middleware(['nova'])
            ->prefix('nova-vendor/auto-translator')
            ->group(__DIR__ . '/../routes/auto-translator.php');

        $this->loadMigrations();

        $this->registerRoutes();

        $this->publishConfiguration();

        $this->loadTranslations();

        $this->registerHelpers();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfiguration();

        $this->registerCommands();

        $this->registerContainerBindings();

        $this->registerMiddleware();

        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Register the middleware
     *
     * @param string $middleware
     */
    protected function registerMiddleware(): void
    {
        $middleware = \Marshmallow\Translatable\Http\Middleware\Localization::class;
        $kernel = $this->app[Kernel::class];
        $kernel->appendMiddlewareToGroup('web', $middleware);
    }

    /**
     * Register package routes.
     *
     * @return void
     */
    private function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');
    }

    /**
     * Publish package configuration.
     *
     * @return void
     */
    private function publishConfiguration(): void
    {
        $this->publishes([
            __DIR__ . '/../config/translatable.php' => config_path('translatable.php'),
        ], 'config');
    }

    /**
     * Merge package configuration.
     *
     * @return void
     */
    private function mergeConfiguration(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/translatable.php', 'translatable');
    }

    /**
     * Load package migrations.
     *
     * @return void
     */
    private function loadMigrations(): void
    {
        if (config('translatable.driver') !== 'database') {
            return;
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Load package translations.
     *
     * @return void
     */
    private function loadTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'translatable');

        $this->publishes([
            __DIR__ . '/../resources/lang' => resource_path('lang/vendor/translatable'),
        ]);
    }

    /**
     * Register package helper functions.
     *
     * @return void
     */
    private function registerHelpers(): void
    {
        require __DIR__ . '/../resources/helpers.php';
    }

    /**
     * Register package commands.
     *
     * @return void
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AddLanguageCommand::class,
                AddTranslationKeyCommand::class,
                SynchroniseTranslationsFromToCommand::class,
                ListLanguagesCommand::class,
                ListMissingTranslationKeys::class,
                SynchroniseMissingTranslationKeys::class,
                SynchroniseTranslationsCommand::class,
                PresetCommand::class,
                GeneratePresetCommand::class,
                InstallCommand::class,
                DuplicateTranslationsCommand::class,
                IndexMissingTranslatables::class,
                FixTranslatedPlaceholdersCommand::class,
                TranslatableCacheCommand::class,
                TranslatableClearCommand::class,
                TranslationCacheCommand::class,
                TranslationClearCommand::class,
            ]);
        }
    }

    /**
     * Register package bindings in the container.
     *
     * @return void
     */
    private function registerContainerBindings(): void
    {
        $this->app->singleton(Scanner::class, function () {
            $config = $this->app['config']['translatable'];

            return new Scanner(new Filesystem, $config['scan_paths'], $config['translation_methods']);
        });

        $this->app->singleton(Translation::class, function ($app) {
            return (new TranslationManager($app, $app['config']['translatable'], $app->make(Scanner::class)))->resolve();
        });
    }
}
