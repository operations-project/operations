# Drupal Settings

This PHP Package is a smart include for Drupal settings.php.

Add it to your project to simplify your settings.php file by using common defaults for each hosting vendor and environment types.

## Usage


1. Install with composer:
  
       composer require drupal-operations/drupal-settings`

2. Add snippet to sites/default/settings.php:

       if (file_exists(DRUPAL_ROOT . "/../vendor/drupal-operations/drupal-settings/Settings/settings.include.php")) {
           require DRUPAL_ROOT . "/../vendor/drupal-operations/drupal-settings/Settings/settings.include.php";
       }

3. Profit.

To override any defaults provided by [`settings.include.php`](./Settings/settings.include.php), simply add them to your settings.php file after the snippet.

## Components

1. [Drupal Settings Include File](./Settings/settings.include.php) - settings.include.php

    Include this file from settings.php and remove all the extra settings.

2. [Vendor-specific settings files](./Settings/Vendors) - Included automatically when their environment variables are detected.

2. [Composer Autoload File](./Settings/autoload.php) - autoload.php

    This file is included in your site's Autoloader as early as possible. 
    
    It is used to set Environment variables like DRUSH_OPTIONS_URI automatically.

## Features

This tool attempts to automate as much configuration as possible for multiple host providers.

The main features are:

1. Detect host vendor environments and includes `settings.VENDOR.php` files automatically.
2. Sets `$databases` credentials from host vendor information, or uses `MYSQL_USER`-style variables.   
2. Sets `DRUSH_OPTIONS_URI` globally so any call to drush has the correct URL.
3. Sets `DRUPAL_ENV` environment variable to `prod` when a production environment is detected. (Defaults to `dev`).
4. Includes environment specific `settings.DRUPAL_ENV.php` files from this project and `sites/default/settings.DRUPAL_ENV.php` from your site's source code  if it exists.
4. Automatically enables development features when `DRUPAL_ENV==dev` by including Drupal's `example.settings.local.php`.

## Supported Providers

1. Lando
2. DDEV
3. Platform.sh
4. Acquia
5. OpenDevShop
