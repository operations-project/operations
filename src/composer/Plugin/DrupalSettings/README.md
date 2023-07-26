# Drupal Settings

This PHP Package is a smart include for Drupal settings.php.

Add it to your project to simplify your settings.php file by using common defaults for each hosting vendor and environment types.

It just makes things easier. For example, when using Lando, you don't have to set the database settings or DRUSH_OPTIONS_URI, and it automatically enables development mode.
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
   
    Include this file from settings.php and remove all the extra settings. For full documentation on what it does, see 
    the file [./Settings/settings.include.php](./Settings/settings.include.php).

2. [Vendor-specific settings files](./Settings/Vendors) - Included automatically when their environment variables are detected.

3. [Composer Autoload File](./Settings/autoload.php) - autoload.php

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

We invite all other systems to submit merge requests to the project here: https://git.drupalcode.org/project/ox/-/tree/1.x/src/composer/Plugin/DrupalSettings/Settings/Vendors

## Development

This tool is a part of the [Drupal Operations / OX project](https://drupal.org/project/ox). The code is maintained in the monorepo "ox".

See https://git.drupalcode.org/project/ox for more information.
