<?php
/**
 * @file settings.lando.php
 * Lando Local Development Settings.
 */

# Set ENV vars for site.module
putenv('DRUPAL_SITE_HOST_PROVIDER=lando');
putenv('DRUPAL_ENV=dev');

$lando_info = json_decode(getenv('LANDO_INFO'), TRUE);
$settings['hash_salt'] = md5(getenv('LANDO_HOST_IP'));


# See https://www.drupal.org/docs/getting-started/installing-drupal/trusted-host-settings

// Allow all hostnames. It's local.
$settings['trusted_host_patterns'] = ['.*'];

// This will prevent Drupal from setting read-only permissions on sites/default.
$settings['skip_permissions_hardening'] = TRUE;

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
