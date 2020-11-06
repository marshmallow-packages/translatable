<?php

namespace Marshmallow\Translatable;

use Illuminate\Translation\TranslationServiceProvider as ServiceProvider;
use Illuminate\Translation\Translator;
use Marshmallow\HelperFunctions\Facades\URL;
use Marshmallow\Translatable\Scanner\ContractDatabaseLoader;
use Marshmallow\Translatable\Scanner\Drivers\Translation;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register package bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->registerDatabaseTranslator();
    }

    private function registerDatabaseTranslator()
    {
        $this->registerDatabaseLoader();

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            if (URL::isNova(request())) {
                $locale = request()->getTranslatableLocale();
            } else {
                $locale = request()->getUserLocale();
            }

            $trans = new Translator($loader, $locale);
            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }

    protected function registerDatabaseLoader()
    {
        $this->app->singleton('translation.loader', function () {
            return new ContractDatabaseLoader($this->app->make(Translation::class));
        });
    }
}
