<?php
/**
 * @file settings.acquia.php
 * Acquia Cloud Settings
 */

putenv('DRUPAL_SITE_HOST_PROVIDER=acquia');
if ("prod" == getenv('AH_SITE_ENVIRONMENT')) {
  putenv('DRUPAL_ENV=prod');
}

/**
 * Include Acquia settings.
 *
 * If acquia/blt is used, use that.
 */
if (file_exists(DRUPAL_ROOT . "/../vendor/acquia/blt/settings/blt.settings.php")) {
  require DRUPAL_ROOT . "/../vendor/acquia/blt/settings/blt.settings.php";
}

/**
 * Include global acquia site settings.
 * @see https://docs.acquia.com/cloud-platform/manage/code/require-line/
 */
elseif (file_exists('/var/www/site-php')) {
  require '/var/www/site-php/' . $_ENV['AH_SITE_GROUP'] . '/' . $_ENV['AH_SITE_GROUP'] . '-settings.inc';
}
