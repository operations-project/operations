<?php

/**
 * This file is included very early. See autoload.files in composer.json and
 * https://getcomposer.org/doc/04-schema.md#files
 */

use Dotenv\Dotenv;

/**
 * Load any .env file. See /.env.example.
 *
 * Drupal has no official method for loading environment variables and uses
 * getenv() in some places.
 */
$dotenv = Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->safeLoad();


/**
 * Load LANDO stuff
 */
/**
 * Set DRUSH_OPTIONS_URI from lando info, if it exists.
 * This has to be here because settings.lando.php is loaded too late.
 */
if ((bool) getenv('LANDO') && empty(getenv('DRUSH_OPTIONS_URI'))) {
  $lando_info = json_decode(getenv('LANDO_INFO'), TRUE);

  // Set DRUSH_OPTIONS_URI unless already set.
  $main_url = null;
  foreach ($lando_info['appserver']['urls'] as $url) {
    if (strpos($url, 'https') === 0) {
      if (str_contains($url, getenv('LANDO_APP_NAME'))) {
        $main_url = $url;
      }
    }
  }
  if ($main_url) {
    putenv('DRUSH_OPTIONS_URI=' . $main_url);
  }
}

/**
 * If LANDO, set database and enable development mode.
 */
if ((bool) getenv('LANDO')) {

  $lando_info = json_decode(getenv('LANDO_INFO'), TRUE);
  $settings['hash_salt'] = md5(getenv('LANDO_HOST_IP'));

# Set ENV vars for site.module
  putenv('DRUPAL_SITE_HOST_PROVIDER=lando');

# See https://www.drupal.org/docs/getting-started/installing-drupal/trusted-host-settings

# Add all lndo.site urls to trusted_host_patterns.
  $settings['trusted_host_patterns'] = [
    # Lando Proxy
    '\.lndo\.site$',
    '\.internal$',
    # Lando Share
    '\.localtunnel\.me$',
  ];

  // Do not redirect to www if using httpswww module.
  $config['httpswww.settings']['prefix'] = 'no';

  // To alter what database host is used, set LANDO_DATABASE_HOST.
  $lando_database_host = getenv('LANDO_DATABASE_HOST') ?: 'database';

  // Set 'standard' env vars.
  putenv('MYSQL_DATABASE=' . $lando_info[$lando_database_host]['creds']['database']);
  putenv('MYSQL_USER=' . $lando_info[$lando_database_host]['creds']['user']);
  putenv('MYSQL_PASSWORD=' . $lando_info[$lando_database_host]['creds']['password']);
  putenv('MYSQL_HOSTNAME=' . $lando_info[$lando_database_host]['internal_connection']['host']);
  putenv('MYSQL_PORT=' . $lando_info[$lando_database_host]['internal_connection']['port']);

}