<?php

namespace Marshmallow\Translatable;

use Request;
use Laravel\Nova\Nova;
use Marshmallow\Seoable\Seo;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Translation\Translator;
use Illuminate\Support\Facades\Session;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Scanner\Scanner;
use Marshmallow\Translatable\Http\Middleware\Authorize;
use Marshmallow\Translatable\Scanner\TranslationManager;
use Marshmallow\Translatable\Scanner\Drivers\Translation;
use Marshmallow\Translatable\Scanner\ContractDatabaseLoader;
use Marshmallow\Translatable\Scanner\InterfaceDatabaseLoader;
// use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Translation\TranslationServiceProvider as BaseServiceProvider;
use Marshmallow\Translatable\Scanner\Console\Commands\ListMissingTranslationKeys;
use Marshmallow\Translatable\Scanner\Console\Commands\SynchroniseTranslationsCommand;
use Marshmallow\Translatable\Scanner\Console\Commands\SynchroniseMissingTranslationKeys;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    	Request::macro('setTranslatableLocale', function(Language $language) {
    		Session::put('translatable-locale', $language->language);
		});

    	Request::macro('getTranslatableLocale', function() {
    		if ($session = Session::get('translatable-locale')) {
    			return $session;
    		}
    		return config('app.locale');
		});

		Nova::serving(function (ServingNova $event) {
            Nova::script('language-toggle-field', __DIR__.'/../dist/js/field.js');
            Nova::style('language-toggle-field', __DIR__.'/../dist/css/field.css');
        });

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');
        $this->publishes([
            __DIR__ . '/../config/translatable.php' => config_path('translatable.php'),
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/translatable.php', 'translatable');

        $this->registerDatabaseTranslator();
        $this->registerCommands();
        $this->registerContainerBindings();

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
            return new Scanner(new Filesystem, $config['scan_paths'], $config['translation_methods']);
        });

        $this->app->singleton(Translation::class, function ($app) {
            return (new TranslationManager($app, $app['config']['translatable'], $app->make(Scanner::class)))->resolve();
        });
    }

    protected function registerDatabaseTranslator()
    {
        $this->registerDatabaseLoader();

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];
            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app['config']['app.locale'];
            $trans = new Translator($loader, $locale);
            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }

    protected function registerDatabaseLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new ContractDatabaseLoader($this->app->make(Translation::class));
        });
    }
}
