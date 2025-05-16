# Firebird for Laravel

[![Latest Stable Version](https://poser.pugx.org/danidoble/laravel-firebird/v/stable)](https://packagist.org/packages/danidoble/laravel-firebird)
[![Total Downloads](https://poser.pugx.org/danidoble/laravel-firebird/downloads)](https://packagist.org/packages/danidoble/laravel-firebird)
[![Tests](https://github.com/danidoble/laravel-firebird/actions/workflows/tests.yml/badge.svg)](https://github.com/danidoble/laravel-firebird/actions/workflows/tests.yml)
[![License](https://poser.pugx.org/danidoble/laravel-firebird/license)](https://packagist.org/packages/danidoble/laravel-firebird)

This package adds support for the Firebird PDO Database Driver in Laravel applications.

## Version Support

- **PHP:** 8.1, 8.2, 8.3, 8.4
- **Laravel:** 10.x, 11.x, 12.x
- **Firebird:** 2.5, 3.0, 4.0, 5.0

## Installation

You can install the package via composer:

```bash
composer require danidoble/laravel-firebird
```

_The package will automatically register itself._

Declare the connection within your `config/database.php` file by using `firebird` as the
driver:

```php
'connections' => [

    'firebird' => [
        'driver'   => 'firebird',
        'host'     => env('DB_HOST', 'localhost'),
        'port'     => env('DB_PORT', '3050'),
        'database' => env('DB_DATABASE', '/path_to/database.fdb'),
        'username' => env('DB_USERNAME', 'sysdba'),
        'password' => env('DB_PASSWORD', 'masterkey'),
        'charset'  => env('DB_CHARSET', 'UTF8'),
        'role'     => null,
        'legacy_limit_and_offset' => true, // for Firebird 3.0 and above set to false
    ],

],
```

## Limitations

This package does not intend to support database migrations and it should not be used for this use case.

## Credits

- [Harry Gulliford](https://github.com/harrygulliford)
- [Jacques van Zuydam](https://github.com/jacquestvanzuydam/laravel-firebird)
- [Simonov Denis](https://github.com/sim1984/laravel-firebird)
- [Danidoble](https://github.com/danidoble)
- [All Contributors](https://github.com/harrygulliford/laravel-firebird/graphs/contributors)

## License

Licensed under the [MIT](https://choosealicense.com/licenses/mit/) license.
