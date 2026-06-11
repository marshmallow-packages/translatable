![alt text](https://marshmallow.dev/cdn/media/logo-red-237x46.png "marshmallow.")

# Nova Translatable

[![Latest Version on Packagist](https://img.shields.io/packagist/v/marshmallow/translatable.svg?style=flat-square)](https://packagist.org/packages/marshmallow/translatable)
[![Tests](https://img.shields.io/github/actions/workflow/status/marshmallow-packages/translatable/php-syntax-checker.yml?branch=main&label=tests&style=flat-square)](https://github.com/marshmallow-packages/translatable/actions/workflows/php-syntax-checker.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/marshmallow/translatable.svg?style=flat-square)](https://packagist.org/packages/marshmallow/translatable)
[![License](https://img.shields.io/github/license/marshmallow-packages/translatable?style=flat-square)](https://github.com/marshmallow-packages/translatable/blob/main/LICENSE.md)

Add translation to your Nova Resources. The translations will be stored in a `translatables` table and not in a JSON format in your existing tables as many packages out there do.

<img src="https://raw.githubusercontent.com/marshmallow-packages/translatable/main/resources/screenshots/translatable.png"/>

## Installation

You can install the package via composer:

```bash
composer require marshmallow/translatable
```

Publish the configuration:

```bash
php artisan vendor:publish --provider="Marshmallow\Translatable\ServiceProvider"
```

Run the install command. This updates `config/app.php`, syncs your existing language files into the database, syncs inline translations and generates the Nova resources:

```bash
php artisan translatable:install
```

## Manual Installation

If you prefer to install manually or the automatic installation doesn't work for your setup, follow these steps:

### 1. Add Default Locale Configuration

Add the following line to your `config/app.php` file, right after the `'locale'` configuration:

```php
'locale' => env('APP_LOCALE', 'en'),

'default_locale' => env('APP_LOCALE'),
```

### 2. Set Environment Variable

Make sure you have the `APP_LOCALE` environment variable set in your `.env` file:

```env
APP_LOCALE=en
```

### 3. Run Migration and Synchronization Commands

```bash
# Sync existing translation files to database
php artisan translatable:sync-file-to-database

# Sync missing translations
php artisan translatable:sync-missing

# Generate Nova resources (if using Laravel Nova)
php artisan marshmallow:resource Language Translatable --force
php artisan marshmallow:resource Translation Translatable --force
```

## Usage

Please reference the official documentation at [Marshmallow Documentation](https://mrmallow.notion.site/Translatable-1c76ed0c3dbf8079b010fff1afc71986).

### Make a model translatable

Add the `Translatable` trait to any Eloquent model whose attributes you want to translate. When the trait is enabled, translated values are stored in the `translatables` table and the `translatable` relationship is eager loaded automatically to avoid N+1 queries.

```php
use Illuminate\Database\Eloquent\Model;
use Marshmallow\Translatable\Traits\Translatable;

class Article extends Model
{
    use Translatable;
}
```

### Use Deepl integration

This package contains an integration with Deepl. This integration will add a button to the translations index view, that will automaticly translate your text via Deepl. Just add the following ENV variable to use this awesome feature.

```env
TRANSLATABLE_DEEPL_API_KEY=
```

This will use the free version of the Deepl API. If you have a paid subscription, you can add the following as well.

```env
TRANSLATABLE_DEEPL_API_PATH=https://api.deepl.com
```

## Configuration

After publishing, the configuration lives in `config/translatable.php`:

| Key | Default | Description |
| --- | --- | --- |
| `driver` | `database` | Translation storage driver. Supported: `file`, `database`. Migrations are only loaded when set to `database`. |
| `nova_translatable_fields` | `true` | Show the language selector when editing a resource in Laravel Nova. |
| `flag_icon` | `['height' => 40, 'width' => 40]` | Ratios for the flag uploader. |
| `translation_methods` | `['trans', '__']` | Methods the scanner looks for when finding missing translations. |
| `scan_paths` | `[app_path(), resource_path()]` | Directories scanned when looking for missing translations. |
| `models` | `Language`, `Translation`, `Translatable`, `MissingTranslation` | Model classes used by the package. Override to swap in your own. |
| `force_locale_query_string` | `force_locale` | Query string parameter that forces the locale, useful for testing and deep linking. |
| `deepl.api_path` | `env('TRANSLATABLE_DEEPL_API_PATH', 'https://api-free.deepl.com')` | Deepl API endpoint. |
| `deepl.api_key` | `env('TRANSLATABLE_DEEPL_API_KEY')` | Deepl API key. |
| `auto_translator.active` | `env('TRANSLATABLE_AUTO_TRANSLATOR_ACTIVE', false)` | Enable the auto-translator. |
| `missing_translations.active` | `env('MISSING_TRANSLATIONS_ACTIVE', false)` | Enable missing-translation tracking. |

## Artisan Commands

The package ships the following Artisan commands:

| Command | Description |
| --- | --- |
| `translatable:install` | Install the package: update config, sync files to database and generate Nova resources. |
| `translatable:add-language` | Add a new language. |
| `translatable:add-translation-key` | Add a new translation key. |
| `translatable:list-languages` | List the configured languages. |
| `translatable:list-missing` | List missing translation keys. |
| `translatable:sync-file-to-database` | Sync existing language files into the database. |
| `translatable:sync-missing` | Sync missing translation keys. |
| `translatable:sync-translations` | Synchronise translations. |
| `translatable:duplicates` | Find duplicate translations. |
| `translatable:fix-placeholders` | Fix translated placeholders. |
| `translatable:generate-preset` | Generate a preset. |
| `translatable:preset` | Apply a preset. |
| `translatable:index-missing-translatables` | Index missing translatables. |

## Security

If you discover any security related issues, please email stef@marshmallow.dev instead of using the issue tracker.

## Credits

-   [Stef van Esch](https://github.com/marshmallow-packages)
-   [All Contributors](../../contributors)
-   [joedixon](https://github.com/joedixon/laravel-translation)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
