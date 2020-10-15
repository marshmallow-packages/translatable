<?php

namespace Marshmallow\Translatable\Scanner\Console\Commands;

use Illuminate\Support\Facades\DB;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Models\Translation;

class SynchroniseMissingTranslationKeys extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:sync-missing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all of the missing translation keys for all languages or a single language';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->translation->saveMissingTranslations();
            $this->createTranslationsForAllLanguages();

            return $this->info('✅ Saving missing translations found in your code to the database');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * If a translation exists for EN but not for NL, this method
     * will create it.
     */
    protected function createTranslationsForAllLanguages()
    {
        $languages = Language::get();
        $translations = Translation
                                ::select('*', DB::raw('count(*) as total'))
                                ->groupBy(['key', 'group'])
                                ->get()
                                ;

        foreach ($translations as $translation) {
            if ($languages->count() == $translation->total) {
                continue;
            }
            foreach ($languages as $language) {
                $value = ($translation->group !== 'single') ? $translation->value : '';

                $language
                    ->translations()
                    ->updateOrCreate([
                        'group' => $translation->group,
                        'key' => $translation->key,
                    ], [
                        'key' => $translation->key,
                        'value' => $value,
                    ]);
            }
        }

        return 0;
    }
}
