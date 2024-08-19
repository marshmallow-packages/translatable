![alt text](https://marshmallow.dev/cdn/media/logo-red-237x46.png "marshmallow.")

# Nova Translatable

[![Version](https://img.shields.io/packagist/v/marshmallow/translatable)](https://github.com/marshmallow-packages/translatable)
[![Issues](https://img.shields.io/github/issues/marshmallow-packages/translatable)](https://github.com/marshmallow-packages/translatable)
[![Code Coverage](https://img.shields.io/badge/coverage-100%25-success)](https://github.com/marshmallow-packages/translatable)
[![Licence](https://img.shields.io/github/license/marshmallow-packages/translatable)](https://github.com/marshmallow-packages/translatable)

Add translation to your Nova Resources. The translations will be stored in a `translatables` table and not in a JSON format in your existing tables as many packages out there do.

<img src="https://raw.githubusercontent.com/marshmallow-packages/translatable/main/resources/screenshots/translatable.png"/>

## Installation

You can install the package via composer:

```bash
composer require marshmallow/translatable
```

Publish configuration and assets

`php artisan vendor:publish --provider="Marshmallow\Translatable\ServiceProvider"`

## Usage

Please reference the official documentation at [MrMallow Documentation](https://mrmallow.nl/packages/nova/translatable.html)

### Use Deepl integration
This package contains an integration with Deepl. This integration will add a button to the translations index view, that will automaticly translate your text via Deepl. Just add the following ENV variable to use this awesome feature.

```env
TRANSLATABLE_DEEPL_API_KEY=
```
This will use the free version of the Deepl API. If you have a paid subscription, you can add the following as well.
```env
TRANSLATABLE_DEEPL_API_PATH=https://api.deepl.com
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

```bash
composer test
```

## Security

If you discover any security related issues, please email stef@marshmallow.dev instead of using the issue tracker.

## Credits

-   [All Contributors](../../contributors)
-   [joedixon](https://github.com/joedixon/laravel-translation)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
