<?php

namespace Marshmallow\Translatable;

use Illuminate\Translation\Translator;
use Marshmallow\HelperFunctions\Facades\URL;
use Marshmallow\Translatable\Scanner\Drivers\Translation;
use Marshmallow\Translatable\Scanner\ContractDatabaseLoader;
use Illuminate\Translation\TranslationServiceProvider as ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register package bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app['config']['translatable.driver'] === 'database') {
            $this->registerDatabaseTranslator();
        } else {
            parent::register();
        }
    }

    private function registerDatabaseTranslator()
    {
        $this->registerDatabaseLoader();

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            $locale = $this->getLocale($app);

            $trans = new Translator($loader, $locale);
            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }

    protected function getLocale($app)
    {
        if (URL::isNova(request())) {
            if (request()::hasMacro('getTranslatableLocale')) {
                return request()->getTranslatableLocale();
            }
        } else {
            if (request()::hasMacro('getUserLocale')) {
                return request()->getUserLocale();
            }
        }

        return $app['config']['app.fallback_locale'];
    }

    protected function registerDatabaseLoader()
    {
        $this->app->singleton('translation.loader', function () {
            return new ContractDatabaseLoader($this->app->make(Translation::class));
        });
    }
}
