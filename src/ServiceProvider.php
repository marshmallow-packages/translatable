<?php

namespace Marshmallow\Translatable;

use Request;
use Laravel\Nova\Nova;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Session;
use Marshmallow\HelperFunctions\Facades\URL;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Scanner\Scanner;
use Marshmallow\Translatable\Scanner\TranslationManager;
use Marshmallow\Translatable\Scanner\Drivers\Translation;
use Marshmallow\Translatable\Console\Commands\PresetCommand;
use Marshmallow\Translatable\Console\Commands\InstallCommand;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Marshmallow\Translatable\Console\Commands\GeneratePresetCommand;
use Marshmallow\Translatable\Console\Commands\DuplicateTranslationsCommand;
use Marshmallow\Translatable\Scanner\Console\Commands\ListMissingTranslationKeys;
use Marshmallow\Translatable\Scanner\Console\Commands\SynchroniseTranslationsCommand;
use Marshmallow\Translatable\Scanner\Console\Commands\SynchroniseMissingTranslationKeys;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        Request::macro('setTranslatableLocale', function (Language $language) {
            Session::put('translatable-locale', $language->language);
        });

        Request::macro('getTranslatableLocale', function () {
            $session_key = (URL::isNova(request())) ? 'translatable-locale' : 'user-locale';
            if ($session = Session::get($session_key)) {
                return $session;
            }

            return config('app.locale');
        });

        Request::macro('setUserLocale', function (Language $language) {
            Session::put('user-locale', $language->language);
        });

        Request::macro('getUserLocale', function () {
            if ($session = Session::get('user-locale')) {
                return $session;
            }

            return config('app.locale');
        });

        Nova::serving(function () {
            Nova::script('language-toggle-field', __DIR__.'/../dist/js/field.js');
            Nova::style('language-toggle-field', __DIR__.'/../dist/css/field.css');
        });

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/routes.php');
        $this->publishes([
            __DIR__.'/../config/translatable.php' => config_path('translatable.php'),
        ]);
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
    }

    /**
     * Merge package configuration.
     *
     * @return void
     */
    private function mergeConfiguration()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/translatable.php', 'translatable');
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
