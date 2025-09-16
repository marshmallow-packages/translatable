<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the translatable package. This will migrate your database and seed the tables.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->updateAppConfig();
        $this->ensureLanguageDirectoryExists();
        $this->syncFilesToDatabase();
        $this->syncInlineTranslartions();
        $this->generateNovaResources();
    }

    protected function generateNovaResources()
    {
        $this->info('Generating nova resources');
        Artisan::call('marshmallow:resource Language Translatable --force');
        Artisan::call('marshmallow:resource Translation Translatable --force');

        $this->info('Language and Translation resources have been created and are available in Nova');
    }

    protected function syncInlineTranslartions()
    {
        $this->info('Collecting all other translation from the paths provided in your config file');
        Artisan::call('translatable:sync-missing');
        $this->info('All translations are now available');
    }

    protected function syncFilesToDatabase()
    {
        $this->info('Starting migrating language files to database');
        
        try {
            Artisan::call('translatable:sync-file-to-database');
            $this->info('Translation files are now available in the translations table.');
        } catch (DirectoryNotFoundException $e) {
            $this->warn('Language directory not found. This is normal for new installations.');
            $this->info('Skipping file-to-database sync as no language files exist yet.');
        } catch (\Exception $e) {
            $this->error('An error occurred during file sync: ' . $e->getMessage());
            $this->warn('You may need to run this command manually later: php artisan translatable:sync-file-to-database');
        }
    }

    protected function updateAppConfig(): void
    {
        $appConfigPath = config_path('app.php');
        
        if (! File::exists($appConfigPath)) {
            $this->warn('App config file not found, skipping default_locale configuration.');
            return;
        }

        $configContent = File::get($appConfigPath);

        if (str_contains($configContent, "'default_locale'")) {
            $this->info('default_locale configuration already exists in app.php');
            return;
        }

        $localePattern = "/'locale'\s*=>\s*[^,]+,/";
        
        if (preg_match($localePattern, $configContent)) {
            $replacement = function ($matches) {
                return $matches[0] . "\n\n    'default_locale' => env('APP_LOCALE'),";
            };
            
            $updatedContent = preg_replace_callback($localePattern, $replacement, $configContent);
            
            if ($updatedContent && $updatedContent !== $configContent) {
                File::put($appConfigPath, $updatedContent);
                $this->info('Added default_locale configuration to config/app.php');
            } else {
                $this->warn('Could not automatically add default_locale configuration. Please add manually.');
            }
        } else {
            $this->warn('Could not locate locale configuration in app.php. Please add default_locale manually.');
        }
    }

    protected function ensureLanguageDirectoryExists(): void
    {
        $langPath = resource_path('lang');
        
        if (! File::exists($langPath)) {
            File::makeDirectory($langPath, 0755, true);
            $this->info('Created language directory: ' . $langPath);
        }

        // Ensure there's at least a default language directory
        $defaultLocale = config('app.locale', 'en');
        $defaultLangPath = $langPath . DIRECTORY_SEPARATOR . $defaultLocale;
        
        if (! File::exists($defaultLangPath)) {
            File::makeDirectory($defaultLangPath, 0755, true);
            $this->info("Created default language directory: {$defaultLangPath}");
            
            // Create a basic validation.php file to avoid empty directory issues
            $validationPath = $defaultLangPath . DIRECTORY_SEPARATOR . 'validation.php';
            if (! File::exists($validationPath)) {
                File::put($validationPath, "<?php\n\nreturn [\n    // Add your validation messages here\n];\n");
                $this->info('Created basic validation.php file');
            }
        }
    }
}
