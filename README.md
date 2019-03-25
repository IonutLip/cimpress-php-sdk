Printi - cimpress-php-sdk
==========================

Printi Cimpress is wrapper for cimpress api.

## Installing sdk

The recommended way to install Cimpress is through
[Composer](http://getcomposer.org).

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```

Next, run the Composer command to install the latest stable version of Cimpress:

```bash
php composer.phar require printi/cimpress-php-sdk
```

You can then later update notify using composer:

```bash
composer.phar update printi/cimpress-php-sdk
```

## User Guide

Initializing cimpress api:

```php
$cimpress = new Cimpress([
    'authVersion' => 'v1', // 'v1' (default) or `v2`
    'credentials' => [ // Auth Config
        'username'   => 'username@cimpress.net',
        'password'   => 'password',
        'connection' => 'CimpressADFS',
        'scope'      => 'openid name email',
        'api_type'   => 'app',
    ],
    'cacheType' => 'database', // 'database' (default), 'redis' or 'memory'
    'jwtToken' => [ // Cache Config
        'enableCaching' => true, // Default to false

        // Options for 'database' cache type
        'tokenTableName' => 'jwt_token',
        'databaseUrl' => 'mysql://user:pass@samplehost:3306/db?password=pass', // Alternative for databaseDsn
        'databaseDsn' => 'mysql:host=samplehost;port=3306',                    // Alternative for databaseUrl
        'databaseUser' => 'username',                                          // Alternative for databaseUrl
        'databasePassword' => 'password',                                      // Alternative for databaseUrl

        // Options for 'redis' cache type
        'host' => '127.0.0.1',
        'port' => 6379, // Optional
        'database' => '5',
        'password' => 'somepass', // Optional
    ],
    'http' => [
        // All available options for Guzzle constructor. Example:
        'timeout' => 10.0,
    ],
]);
```

Calling cimpress api's

```php
// Call Cimpress - Prepress api's
$cimpress->prepress($api_client_id)->filePrep($fileUrl, $parameterUrl, $callbackUrl);
```
```php
// Call Cimpress - Pdf Processing api's
$cimpress->pdfProcessing($api_client_id)->mergePages($fileUrl, $parameterUrl, $callbackUrl);
```

If you want to use authentication v2, you must configure it and pass `client_id` and `client_secret`:

```php
// Initializing cimpress API (credentials config key is not required for v2)
$cimpress = new Cimpress([
    'authVersion' => 'v2',
    'cacheType' => 'database',
    'jwtToken' => [ // Cache Config
        'enableCaching' => true,
        'tokenTableName' => 'jwt_token',
        'databaseUrl' => 'mysql://user:pass@samplehost:3306/db?password=pass',
    ],
    'http' => [
        'timeout' => 10.0,
    ],
]);

// Calling cimpress api's
$cimpress->prepress($api_client_id, $api_client_secret)->filePrep($fileUrl, $parameterUrl, $callbackUrl);
```
