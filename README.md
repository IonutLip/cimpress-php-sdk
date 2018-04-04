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

Initializing cimpress api

```php
$cimpress = new Cimpress([
    "username"   => 'username@cimpress.net',
    "password"   => 'password',
    "connection" => 'CimpressADFS',
    "scope"      => 'openid name email',
    "api_type"   => 'app',
]);
````
Calling cimpress api's

```php
// Call Cimpress - Prepress api's
$cimpress->prepress($api_client_id)->filePrep($fileUrl, $parameterUrl, $callbackUrl);
````
```php
// Call Cimpress - Pdf Processing api's
$cimpress->pdfProcessing($api_client_id)->mergePages($fileUrl, $parameterUrl, $callbackUrl);
````