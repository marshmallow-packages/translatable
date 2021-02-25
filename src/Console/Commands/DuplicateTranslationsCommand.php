<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Models\Translation;

class DuplicateTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List duplicate transactions and fix them if you want to.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $translations = $this->getDuplicateTranslations();
        if (! $translations->count()) {
            return $this->noDuplicatesFound();
        }

        return $this->whatDoYouWantToDo($translations);
    }

    protected function whatDoYouWantToDo(Collection $translations)
    {
        $action = $this->choice(
            "You have {$translations->count()} duplicate translations. What do you want to do?",
            ['List them', 'Fix them'],
            0
        );

        if ('List them' == $action) {
            return $this->listThem($translations);
        } elseif ('Fix them' == $action) {
            return $this->fixThem($translations);
        }

        return 0;
    }

    protected function listThem(Collection $translations)
    {
        $this->info('Bare with us, we are building a beautifull table for you.');
        $table_rows = $translations->map(function ($item) {
            $occurrences = Translation::where('group', $item->group)
                                        ->where('key', $item->key)
                                        ->where('language_id', $item->language_id)
                                        ->get();

            $occurrence_ids = $occurrences->pluck('id')->toArray();

            return [
                'language_id' => $item->language->name,
                'occurrences' => $item->occurrences,
                'key' => Str::limit($item->key, 75),
                'ids' => join(', ', $occurrence_ids),
            ];
        })->toArray();

        $this->table(
            ['Language', 'Occurrences', 'Key'],
            $table_rows
        );

        if ($this->confirm('Do you want us to fix what we can fix automatically?', true)) {
            $this->fixThem($translations);
        }
    }

    protected function fixThem(Collection $translations)
    {
        $languages = Language::get();

        $deleted_count = 0;

        foreach ($translations as $translation) {
            /**
             * Store everything we want to keep in this array.
             */
            $keep_occurrences = [];

            /**
             * Build an array with all the languages in the database so
             * we can keep track on which languages we have covered.
             */
            $language_found_array = [];
            foreach ($languages as $language) {
                $language_found_array[$language->id] = false;
            }

            /**
             * Get all the duplicate values so we can loop through them and make
             * sense of what we need to keep and what we need to delete.
             */
            $all_occurrences = Translation::where('group', $translation->group)
                                            ->where('key', $translation->key)
                                            ->get();

            /*
             * Keep everything that has been translated. We don't want to
             * delete any manual inputted content.
             */
            foreach ($all_occurrences as $occurrence) {
                if ($occurrence->value) {
                    $keep_occurrences[] = $occurrence->id;
                    $language_found_array[$occurrence->language_id] = true;
                }
            }

            /*
             * If we haven't found a translated record for every language, we need
             * to keep untranslated values for this translation.
             */
            if (! $this->checkIfAllLanguagesAreFound($language_found_array)) {
                foreach ($language_found_array as $language_id => $found) {
                    if ($found) {
                        continue;
                    }
                    $occurrence = $all_occurrences->where('language_id', $language_id)->first();
                    $keep_occurrences[] = $occurrence->id;
                }
            }

            /*
             * Now delete every occurrence that is not in the keep_occurrences array.
             */
            foreach ($all_occurrences as $occurrence) {
                if (! in_array($occurrence->id, $keep_occurrences)) {
                    $occurrence->delete();
                    ++$deleted_count;
                }
            }
        }

        $this->info("We have deleted {$deleted_count} duplicate translations for you.");
        $duplicates = $this->getDuplicateTranslations();
        if ($duplicates->count()) {
            $this->newLine();
            $this->info("🥴 Sadly some of the duplicates could not be delete automatically. There are still {$duplicates->count()} in your database. Run this script again so you can list the translation that you need to check manually.");
        } else {
            $this->info('🎉 Good news! There are no more duplicates. You are good to go.');
        }
    }

    protected function getDuplicateTranslations()
    {
        return Translation::groupBy('key', 'group', 'language_id')
                            ->selectRaw('translations.*, count(*) as occurrences')
                            ->havingRaw('count(*) > 1')
                            ->get();
    }

    protected function checkIfAllLanguagesAreFound($language_found_array)
    {
        foreach ($language_found_array as $language_id => $found) {
            if (! $found) {
                return false;
            }
        }

        return true;
    }

    protected function noDuplicatesFound()
    {
        $this->newLine();
        $this->info('🎉 We didnt find any duplicate transactions. Your database is awesome!');

        return 0;
    }
}
