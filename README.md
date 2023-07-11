<p align="center">
<picture>
  <source media="(prefers-color-scheme: dark)" srcset="/art/socialcard-dark.png">
  <source media="(prefers-color-scheme: light)" srcset="/art/socialcard-light.png">
  <img src="/art/socialcard-light.png" alt="Social Card of Laravel Legal Consent">
</picture>
</p>

# Laravel Legal Consent

[![Latest Version on Packagist](https://img.shields.io/packagist/v/maize-tech/laravel-legal-consent.svg?style=flat-square)](https://packagist.org/packages/maize-tech/laravel-legal-consent)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/maize-tech/laravel-legal-consent/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/maize-tech/laravel-legal-consent/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/maize-tech/laravel-legal-consent/php-cs-fixer.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/maize-tech/laravel-legal-consent/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/maize-tech/laravel-legal-consent.svg?style=flat-square)](https://packagist.org/packages/maize-tech/laravel-legal-consent)

Easily integrate legal documents (like privacy policies, terms of use, etc.) to your application.

## Installation

You can install the package via composer:

```bash
composer require maize-tech/laravel-legal-consent
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="legal-consent-migrations"
php artisan migrate
```

You can publish the config file with:
```bash
php artisan vendor:publish --tag="legal-consent-config"
```

This is the content of the published config file:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Legal document model
    |--------------------------------------------------------------------------
    |
    | Here you may specify the fully qualified class name of the legal document model.
    |
    */

    'legal_document_model' => Maize\LegalConsent\Models\LegalDocument::class,

    /*
    |--------------------------------------------------------------------------
    | Legal consent model
    |--------------------------------------------------------------------------
    |
    | Here you may specify the fully qualified class name of the legal consent model.
    |
    */

    'legal_consent_model' => Maize\LegalConsent\Models\LegalConsent::class,

    /*
    |--------------------------------------------------------------------------
    | Legal document finder
    |--------------------------------------------------------------------------
    |
    | Here you may specify the fully qualified class name of the legal document finder class.
    |
    */

    'legal_document_finder' => Maize\LegalConsent\DefaultLegalDocumentFinder::class,

    /*
    |--------------------------------------------------------------------------
    | Route configurations
    |--------------------------------------------------------------------------
    |
    | Here you may specify whether routes should be enabled or not.
    | You can also customize the routes prefix and middlewares.
    |
    */

    'routes' => [
        'enabled' => true,
        'prefix' => 'legal',
        'name' => 'legal',
        'middleware' => ['api'],
        'endpoints' => [
            'show' => [
                'middleware' => [],
            ],
            'consent' => [
                'middleware' => ['auth:api'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed document types
    |--------------------------------------------------------------------------
    |
    | Here you may specify the list of accepted legal document types
    | for all requests.
    |
    */

    'allowed_document_types' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed acceptable values
    |--------------------------------------------------------------------------
    |
    | Here you may specify the list of accepted values for each legal document
    | consent request.
    |
    */

    'allowed_acceptable_values' => [
        'yes',
        'on',
        '1',
        1,
        true,
        'true',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Here you may specify the amount of time, in seconds, where each legal
    | document is cached to avoid multiple database queries.
    |
    */

    'cache' => [
        'document_ttl' => 3600,
        'document_user_ttl' => 3600,
    ],
];
```

## Usage

### Basic

To use the package, add the `Maize\LegalConsent\HasLegalConsent` trait to the all Authenticatable models you want to handle.

Here's an example including the `HasLegalConsent` trait to both User and Admin models:

``` php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Maize\LegalConsent\HasLegalConsent;

class User extends Authenticatable
{
    use HasLegalConsent;

    protected $fillable = [
        'fist_name',
        'last_name',
        'email',
    ];
}
```

``` php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Maize\LegalConsent\HasLegalConsent;

class Admin extends Authenticatable
{
    use HasLegalConsent;

    protected $fillable = [
        'fist_name',
        'last_name',
        'email',
    ];
}
```

Once done, you must define the list of allowed document types by adding them in the `allowed_document_types` list from `config/legal-consent.php`.

``` php
'allowed_document_types' => [
    'privacy-policy',
    'terms-of-use',
],
```

You can then create one or multiple documents from the DB or, if you wish, you could handle the creation with a CMS.

Here are the fields who should be filled:
- **type**: the document type name
- **body**: the content of the document
- **noted**: additional notes to show for the document
- **published_at**: the date of publication for the given document

Let's say we create a privacy policy document with the publication on 2021-01-01: here's the model entity we would have:

``` php
$legalDocument = [
    "id" => 1,
    "type" => "privacy-policy",
    "body" => "The privacy policy's very long text",
    "notes" => "",
    "published_at" => "2021-01-01",
    "updated_at" => "2021-01-01",
    "created_at" => "2021-01-01",
];
```

You can now call the custom API to retrieve and accept the current document, which can be customized in `config/legal-consent.php`:

#### GET - **/legal/documents/privacy-policy**

This endpoint retrieves the current legal document using the given criteria:
- the document type must be `privacy-policy`
- the published_at date must be earlier than `now()`

The document entries are then ordered by their published_at date in order to pick the latest one published.

The response contains the document id (used for the POST route) along with all its information useful for rendering.
Here is a sample response body:

``` json
{
    "data": {
        "id": 1,
        "type": "privacy-policy",
        "body": "The privacy policy's very long text",
        "notes": "",
        "published_at": "2021-01-01"
    }
}
```

#### POST - **/legal/documents/{id}**

This endpoint stores the consent for the given document from the currently authenticated user.

### Legal Document Listener

You can eventually accept all active legal documents using our listener.

This can be useful, for example, when your application handles the registration of users, and they must accept all legal documents through a checkbox before proceeding.

In this case, all you should do is add the listener to the `Registered` event in `EventServiceProvider`:

``` php
use Maize\LegalConsent\Listeners\AcceptLegalDocumentListener;

/**
 * The event listener mappings for the application.
 *
 * @var array
 */
protected $listen = [
    Registered::class => [
        AcceptLegalDocumentListener::class, // all currently active legal documents will be accepted
        SendEmailVerificationNotification::class,
    ],
];
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/maize-tech/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](https://github.com/maize-tech/.github/security/policy) on how to report security vulnerabilities.

## Credits

- [Enrico De Lazzari](https://github.com/enricodelazzari)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
