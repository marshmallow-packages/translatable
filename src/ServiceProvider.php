<?php

namespace Marshmallow\Translatable;

use Request;
use Laravel\Nova\Nova;
use Illuminate\Support\Facades\App;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
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
use Marshmallow\Translatable\Console\Commands\GeneratePresetCommand;
use Marshmallow\Translatable\Console\Commands\ListMissingTranslationKeys;
use Marshmallow\Translatable\Console\Commands\DuplicateTranslationsCommand;
use Marshmallow\Translatable\Console\Commands\SynchroniseTranslationsCommand;
use Marshmallow\Translatable\Console\Commands\SynchroniseMissingTranslationKeys;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        Request::macro('setTranslatableLocale', function ($language) {
            Session::put('translatable-locale', $language->language);
            Cache::put('translatable-locale', $language->language);
        });

        Request::macro('getTranslatableLocale', function () {
            $session_key = (URL::isNova(request())) ? 'translatable-locale' : 'user-locale';
            $app_locale = App::currentLocale();

            if (Session::has($session_key)) {
                return Session::get($session_key);
            } else {
                Session::put($session_key, $app_locale);
            }

            if (Cache::has($session_key)) {
                return Cache::get($session_key);
            } else {
                Cache::put($session_key, $app_locale);
            }

            return $app_locale;
        });

        Request::macro('setUserLocale', function (Language $language) {
            Session::put('user-locale', $language->language);
            Cache::put('user-locale', $language->language);
            App::setLocale($language->language);
            event(new UserLocaleChanged($language));
        });

        Request::macro('getUserLocale', function () {
            $locale_key = 'user-locale';

            if (Session::has($locale_key)) {
                $locale = Session::get($locale_key);
            } else if (Cache::has($locale_key)) {
                $locale = Cache::get($locale_key);
            } else {
                $locale = App::currentLocale();
            }

            return $locale;
        });

        Nova::serving(function () {
            Nova::script('language-toggle-field', __DIR__ . '/../dist/js/field.js');
            Nova::style('language-toggle-field', __DIR__ . '/../dist/css/field.css');
        });

        $this->loadMigrations();

        $this->registerRoutes();

        $this->publishConfiguration();

        $this->loadTranslations();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfiguration();

        $this->registerCommands();

        $this->registerContainerBindings();

        $this->registerMiddleware();
    }

    /**
     * Register the middleware
     *
     * @param  string $middleware
     */
    protected function registerMiddleware()
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
    private function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');
    }

    /**
     * Publish package configuration.
     *
     * @return void
     */
    private function publishConfiguration()
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
    private function mergeConfiguration()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/translatable.php', 'translatable');
    }

    /**
     * Load package migrations.
     *
     * @return void
     */
    private function loadMigrations()
    {
        if (config('translation.driver') !== 'database') {
            return;
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Load package translations.
     *
     * @return void
     */
    private function loadTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'translatable');

        $this->publishes([
            __DIR__ . '/../resources/lang' => resource_path('lang/vendor/translatable'),
        ]);
    }

    /**
     * Register package commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AddLanguageCommand::class,
                AddTranslationKeyCommand::class,
                ListLanguagesCommand::class,
                ListMissingTranslationKeys::class,
                SynchroniseMissingTranslationKeys::class,
                SynchroniseTranslationsCommand::class,
                PresetCommand::class,
                GeneratePresetCommand::class,
                InstallCommand::class,
                DuplicateTranslationsCommand::class,
            ]);
        }
    }

    /**
     * Register package bindings in the container.
     *
     * @return void
     */
    private function registerContainerBindings()
    {
        $this->app->singleton(Scanner::class, function () {
            $config = $this->app['config']['translatable'];

            return new Scanner(new Filesystem(), $config['scan_paths'], $config['translation_methods']);
        });

        $this->app->singleton(Translation::class, function ($app) {
            return (new TranslationManager($app, $app['config']['translatable'], $app->make(Scanner::class)))->resolve();
        });
    }
}
