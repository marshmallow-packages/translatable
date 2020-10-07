<?php

namespace Marshmallow\Translatable;

use Request;
use Laravel\Nova\Nova;
use Marshmallow\Seoable\Seo;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\Facades\Session;
use Marshmallow\Translatable\Models\Language;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

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

        // $this->app->singleton(Seo::class, function () {
            // return new Seo();
        // });

        // $this->app->alias(Seo::class, 'seo');

        // Nova::serving(function (ServingNova $event) {
            // Nova::script('seoable', __DIR__.'/../dist/js/field.js');
            // Nova::style('seoable', __DIR__.'/../dist/css/field.css');
        // });

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'seoable');
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
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
    }
}
