<?php
/**
 * @file settings.php
 */

$projects = [
  'ox' => 'ox',
  'ox.lndo.site' => 'ox',
  'mercury.lndo.site' => 'mercury',
  'venus.lndo.site' => 'venus',
  'mars.lndo.site' => 'mars',
];

if (isset($projects[$_SERVER['HTTP_HOST']])) {
  $project = $projects[$_SERVER['HTTP_HOST']];
}
else {
  $project = $projects['ox'];
}

$settings['config_sync_directory'] = "../config/{$project}";

if ((bool) getenv('LANDO')) {
  # To disable forced config like caching, set LANDO_PROD_MODE
  # putenv("LANDO_PROD_MODE=TRUE");

  // Pull the subdomain out and use that as the host.
  $host = strtr($_SERVER['HTTP_HOST'], ['.lndo.site' => '']);
  $database_host = $host;

  if ($host == 'ox.lndo.site') {
    $host = 'vsd';
    $database_host = 'database';
  }
  elseif ($host == 'ox') {
    $host = 'ox';
    $database_host = 'database';
  }

  if ($host){
    putenv("LANDO_DATABASE_HOST={$database_host}_db");
    $settings['file_public_path'] = 'sites/' . $host . '/files';
  }

  $uuids = [
    'vsd' => 'd3c85e16-e0db-49f1-b7fd-f13e84f3b9dd',
    'mercury' => 'daaa81bd-5f27-4152-a209-511e31368848',
    'mars' => 'site.mars',
    'venus' => 'site.venus',
  ];
  $config['system.site']['uuid'] = $uuids[$host];

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


if (empty($databases['default']['default'])) {
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
}

// @TODO: Detect appropriate namespace depending on drupal version.
$databases['default']['default']['namespace'] = 'Drupal\\Core\\Database\\Driver\\mysql';

$settings['rebuild_access'] = FALSE;

//
///**
// * Vardot Support Module Settings
// *
// * The file below includes acquia/blt settings, Lando config, and other things globally.
// */
//require DRUPAL_ROOT . "/modules/contrib/vardot_support/settings/settings.vardot.php";

//$config['site_audit_send.settings']['remote_url'] = 'http://vsd.lndo.site/api/site-audit?api-key=TESTKEY';

// DSD/Site Manager settings.

// The full URL to send site reports to.
if ($projects[$_SERVER['HTTP_HOST']] != 'vsd') {

// The state values to allow overriding in this site.
  $config['site.site_definition.self']['states_allow_override'] = [
    'vardot_subscription_end_date',
    'vardot_subscription_support_url',
    'vardot_subscription_support_widget',
    'vardot_subscription_is_active',
  ];

// The config items to send along with the report.
// use main config name, or value in the format system.site:name
  $config['site.site_definition.self']['configs_load'] = [
    'core.extension',
    'system.cron',
    'system.site',
  ];
  $config['site.site_definition.self']['settings']['save_on_config'] = true;
  $config['site.site_definition.self']['settings']['send_on_save'] = true;

  # Un hard code for testing.
  # $config['site.site_definition.self']['settings']['send_interval'] = 60;
  # $config['site.site_definition.self']['settings']['save_interval'] = 0;

  if ((bool) getenv('LANDO')) {
    $config['site.site_definition.self']['settings']['send_destinations'] = "https://appserver.vsd.internal/api/site/data?api-key=9f6dfc256451638821d1c88f46ea659d";
  }
  else {
    $config['site.site_definition.self']['settings']['send_destinations'] = "https://vsd.demo.support.devshop.cloud/api/site/data?api-key=c2895f91b56dc5b1e952645760a584c8";
  }
}
