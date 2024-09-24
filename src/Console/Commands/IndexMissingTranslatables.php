<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Database\Eloquent\Model;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Console\Commands\BaseCommand;

class IndexMissingTranslatables extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:index-missing-translatables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index all missing translations into the database.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!config('translatable.missing_translations.active')) {
            $this->error('Missing translations are not active');
            return;
        }

        $items = Language::getTranslatableModels();

        collect($items)->each(function ($model) {
            $count = $model::cursor()->count();
            $model_name = explode('\\', $model);
            $model_name = end($model_name);

            $this->info("Syncing {$count} {$model_name} models");

            $this->withProgressBar($model::cursor(), function (Model $model) {
                $model->updateMissingTranslations();
            });

            $this->newLine();
            $this->newLine();
        });
    }
}
