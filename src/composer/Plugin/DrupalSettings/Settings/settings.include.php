<?php

/**
 * @file
 * settings.include.php
 *
 * Global settings for Drupal sites. This file is designed to be included from
 * Drupal's settings.php file, usually located at /sites/default/settings.php.
 *
 * To use this file, put something like this in your settings.php file:
 *
 * if (file_exists($app_root . '/../vendor/drupal-operations/drupal-settings/Settings/settings.include.php')) {
 *   include $app_root . '/../vendor/drupal-operations/drupal-settings/Settings/settings.include.php';
 * }
 *
 */

/**
 * Vendor Includes
 *
 * Detects environment variables and includes the appropriate file.
 *
 */
switch (true) {

  /**
   * Lando
   * https://lando.dev/
   * https://github.com/lando/lando/
   */
  case (bool) getenv('LANDO'):
    require __DIR__ . '/Vendors/settings.lando.php';
    break;

  /**
   * DDEV
   * https://www.drupal.org/ddev
   */
  case (bool) getenv('IS_DDEV_PROJECT'):
    require __DIR__ . '/Vendors/settings.ddev.php';
    break;

  /**
   * Acquia Cloud
   * https://cloud.acquia.com
   */
  case (bool) getenv('AH_SITE_ENVIRONMENT'):
    require __DIR__ . '/Vendors/settings.acquia.php';
    break;

  /**
   * Platform.sh
   * https://platform.sh/
   */
  case (bool) getenv('PLATFORM_ENVIRONMENT'):
    require __DIR__ . '/Vendors/settings.platformsh.php';
    break;

  /**
   * OpenDevShop
   * https://github.com/opendevshop/devshop
   */
  case (bool) getenv('PLATFORM_ENVIRONMENT'):
    require __DIR__ . '/Vendors/settings.platform.php';
    break;

  /**
   * YourHostHere
   * https://drupal.org/project/ox
   *
   * Submit a Merge Request to add your hosting provider.
   */
  case (bool) getenv('YOUR_HOST'):
    require __DIR__ . '/Vendors/settings.yourhost.php';
    break;
}


/**
 * Set DRUPAL_ENV.
 *
 * If not previously set in the vendor includes, assume DRUPAL_ENV is "dev".
 *
 */
if (!(bool) getenv('DRUPAL_ENV')) {
  putenv('DRUPAL_ENV=dev');
}

/**
 * Include environment specific settings.
 *
 * Include the global settings file in drupal-operations/drupal-settings based on DRUPAL_ENV.
 */
if (file_exists('Environments/settings' . getenv('DRUPAL_ENV') . '.php')) {
  include('Environments/settings' . getenv('DRUPAL_ENV') . '.php');
}

/**
 * Include environment specific settings from project.
 *
 * Include an environment settings file in the project source code, if it exists.
 */
if (file_exists($app_root . '/' . $site_path . '/settings.'. getenv('DRUPAL_ENV') . '.php')) {
  include($app_root . '/' . $site_path . '/settings.'. getenv('DRUPAL_ENV') . '.php');
}

/**
 * Set Config Directory.
 *
 * If config_sync_directory has not been set, use ../config/sync
 */
if (empty($settings['config_sync_directory'])) {
  $settings['config_sync_directory'] = '../config/sync';
}

/**
 * Set 'rebuild_access' to false, by default.
 */
$settings['rebuild_access'] = FALSE;

/**
 * If database connection has not yet been defined, and MYSQL_DATABASE env vars exist, set $databases.
 */
if (empty($databases['default']['default']) && !empty(getenv('MYSQL_DATABASE'))) {
  // Global database settings from ENV vars.
  // These can be set a number of ways:
  // - settings.HOST.php can automatically detect them.
  $databases['default']['default'] = [
    'driver' => 'mysql',
    'database' => getenv('MYSQL_DATABASE'),
    'username' => getenv('MYSQL_USER'),
    'password' => getenv('MYSQL_PASSWORD'),
    'host' => getenv('MYSQL_HOSTNAME'),
    'port' => getenv('MYSQL_PORT'),
    'prefix' => '',
    'init_commands' => [
      'isolation_level' => 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED',
    ],
  ];
  /// @TODO: determine this automatically. Drupal has a method for rewriting settings.php. Use that.
  $databases['default']['default']['namespace'] = 'Drupal\\Core\\Database\\Driver\\mysql';
}
