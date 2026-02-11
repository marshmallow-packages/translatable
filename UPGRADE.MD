# Upgrade Guide (v1 → v2)

## Overview

Version 2 is a complete rewrite with improved performance, better Nova UX, and AI translation support. This guide covers all breaking changes and migration steps.

## Pre-Migration Checklist

- [ ] Backup your database
- [ ] Note which models use the `Translatable` trait
- [ ] Export current translations (optional backup)
- [ ] Review Nova resources using translation features

## Step 1: Update Composer

```bash
composer require marshmallow/translatable:^2.0
```

## Step 2: Run Migrations

```bash
php artisan migrate
```

The migrations automatically:
- Rename `languages.language` → `languages.code`
- Rename `translatables.source_field` → `translatables.field`
- Rename `translatables.translated_value` → `translatables.value`
- Add new columns: `context`, `source`, `is_locked`, `imported_at`
- Drop `missing_translations` table (now computed on-demand)

## Step 3: Update Configuration

**Old config (v1):**
```php
'driver' => 'database',
'deepl' => [
    'api_path' => '...',
    'api_key' => '...',
],
```

**New config (v2):**
```php
'default_language' => 'en',
'translators' => [
    'default' => 'deepl',
    'deepl' => [
        'api_url' => '...',  // renamed from api_path
        'api_key' => '...',
    ],
    'openai' => [...],
    'anthropic' => [...],
],
```

Publish new config:
```bash
php artisan vendor:publish --tag=translatable-config --force
```

## Step 4: Update Model Usage

**No changes required** - the trait API is backward compatible:

```php
// Still works
use Marshmallow\Translatable\Traits\Translatable;

class Product extends Model
{
    use Translatable;

    protected function translatableColumns(): array
    {
        return ['title', 'description'];
    }
}
```

## Step 5: Update Nova Resources

**Old approach (v1):**
```php
// Using TranslatableFields trait + LanguageToggler
class Product extends Resource
{
    use TranslatableFields;

    public function translatableFields()
    {
        return [
            Text::make('Title'),
        ];
    }
}
```

**New approach (v2):**
```php
// Using TranslatableField wrapper
use Marshmallow\Translatable\Nova\Fields\TranslatableField;

class Product extends Resource
{
    public function fields(Request $request)
    {
        return [
            TranslatableField::make('Title', Text::make('Title')),
            TranslatableField::make('Description', Tiptap::make('Description')),
        ];
    }
}
```

## Step 6: Update Translation Loading

**Old approach (implicit, could cause N+1):**
```php
$products = Product::all();
// Each $product->title might trigger a query
```

**New approach (explicit):**
```php
$products = Product::withTranslations()->get();
// Translations eager loaded, no N+1
```

**Or enable global auto-loading:**
```php
// AppServiceProvider.php
use Marshmallow\Translatable\TranslatableConfig;

public function boot()
{
    TranslatableConfig::autoLoad();
}
```

## Step 7: Update DeepL Usage

**Old (v1):**
```php
use Marshmallow\Translatable\Action\TranslateUsingDeeplAction;

$action = new TranslateUsingDeeplAction();
$translated = $action->handle('Hello', 'en', 'nl');
```

**New (v2):**
```php
use Marshmallow\Translatable\Translators\TranslatorManager;

$manager = app(TranslatorManager::class);
$translated = $manager->driver('deepl')->translate('Hello', 'en', 'nl');

// Or use other translators
$translated = $manager->driver('openai')->translate('Hello', 'en', 'nl');
$translated = $manager->driver('anthropic')->translate('Hello', 'en', 'nl');
```

## Step 8: Cache Management

**Old commands:**
```bash
php artisan translation:cache
php artisan translatable:cache
```

**New commands (same names, improved):**
```bash
php artisan translatable:cache      # Caches all translations
php artisan translatable:clear      # Clears all caches
```

Now hooks into `php artisan optimize` automatically.

## Step 9: Register Translation Matrix Tool (Optional)

If you want to use the Translation Matrix tool in Nova:

```php
// NovaServiceProvider.php
use Marshmallow\Translatable\Nova\Tools\TranslationMatrix;

public function tools()
{
    return [
        new TranslationMatrix(),
    ];
}
```

## Breaking Changes Summary

| v1 | v2 | Migration |
|----|----|----|
| `languages.language` | `languages.code` | Auto-migrated |
| `translatables.source_field` | `translatables.field` | Auto-migrated |
| `translatables.translated_value` | `translatables.value` | Auto-migrated |
| `missing_translations` table | Computed on-demand | Auto-dropped |
| `TranslatableFields` trait | `TranslatableField` wrapper | Manual update |
| `LanguageToggler` field | Built into `TranslatableField` | Manual update |
| `TranslateUsingDeeplAction` | `TranslatorManager` | Manual update |
| Nova Translation resource | Translation Matrix tool | Automatic |
| Nova Language resource | Translation Matrix tool | Automatic |
| Implicit translation loading | Explicit `withTranslations()` | Recommended |
| Config `deepl.api_path` | Config `translators.deepl.api_url` | Manual update |

## New Features in v2

### AI Translation Support
```php
$manager = app(TranslatorManager::class);

// DeepL
$manager->driver('deepl')->translate('Hello', 'en', 'nl');

// OpenAI GPT-4
$manager->driver('openai')->translate('Hello', 'en', 'nl');

// Anthropic Claude
$manager->driver('anthropic')->translate('Hello', 'en', 'nl');

// Batch translation
$manager->driver('deepl')->translateBatch(['Hello', 'World'], 'en', 'nl');
```

### Context Support
Same translation key with different meanings:
```php
// button.submit in checkout context
Translation::create([
    'language_id' => $language->id,
    'group' => 'button',
    'key' => 'submit',
    'context' => 'checkout',
    'value' => 'Place Order',
]);

// button.submit in contact form context
Translation::create([
    'language_id' => $language->id,
    'group' => 'button',
    'key' => 'submit',
    'context' => 'contact',
    'value' => 'Send Message',
]);
```

### Locking Translations
Prevent imports from overwriting manual translations:
```php
$translation->lock();
$translation->unlock();

// Or when creating
Translation::create([
    // ...
    'is_locked' => true,
]);
```

### Import from Laravel-Lang
```bash
php artisan translatable:import laravel
php artisan translatable:import nova
php artisan translatable:import filament
php artisan translatable:import validation
```

### Vendor Package Import
```bash
php artisan translatable:import-vendor spatie /path/to/vendor/translations
```

### Project Scanning
```bash
# Dry run
php artisan translatable:scan --dry-run

# Add found keys to database
php artisan translatable:scan
```

## Troubleshooting

**Q: My translations are not loading**
A: Add `withTranslations()` scope or enable `TranslatableConfig::autoLoad()`

**Q: Nova fields don't show language tabs**
A: Update to use `TranslatableField::make()` wrapper

**Q: Import overwrites my manual translations**
A: Lock translations you want to preserve with `is_locked = true`

**Q: Where are the Nova resources for Language/Translation?**
A: Replaced by Translation Matrix tool - single interface for all translations

**Q: AI translation is not working**
A: Check that API keys are configured in `config/translatable.php` under `translators`

**Q: Cache is not updating**
A: Run `php artisan translatable:clear` and `php artisan translatable:cache`

## Environment Variables

Add these to your `.env` file:

```env
# Default language
TRANSLATABLE_DEFAULT_LANGUAGE=en

# Enable caching
TRANSLATABLE_CACHE_ENABLED=true

# Default translator
TRANSLATABLE_TRANSLATOR=deepl

# DeepL (https://www.deepl.com/pro-api)
DEEPL_API_KEY=your-api-key
DEEPL_API_URL=https://api-free.deepl.com

# OpenAI (https://platform.openai.com/api-keys)
OPENAI_API_KEY=your-api-key
OPENAI_MODEL=gpt-4

# Anthropic (https://console.anthropic.com/)
ANTHROPIC_API_KEY=your-api-key
ANTHROPIC_MODEL=claude-3-sonnet-20240229
```

## Support

If you encounter issues during the upgrade, please:
1. Check this guide for known breaking changes
2. Review the [GitHub Issues](https://github.com/marshmallow-packages/translatable/issues)
3. Create a new issue with:
   - Laravel version
   - Nova version (if applicable)
   - PHP version
   - Error message/logs
   - Steps to reproduce
