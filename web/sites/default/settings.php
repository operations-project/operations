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

$project = $projects[$_SERVER['HTTP_HOST'] ?? 'vsd'];
$settings['config_sync_directory'] = "../config/{$project}";

if ((bool) getenv('LANDO')) {
  # To disable forced config like caching, set LANDO_PROD_MODE
  # putenv("LANDO_PROD_MODE=TRUE");

  // Pull the subdomain out and use that as the host.
  $host = strtr($_SERVER['HTTP_HOST'], ['.lndo.site' => '']);
  $database_host = $host . '_db';

  if ($host == 'ox') {
    $host = 'ox';
    $database_host = 'database';
  }

  if ($host){
    putenv("LANDO_DATABASE_HOST=$database_host");
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

// The full URL to send site reports to.
if ($projects[$_SERVER['HTTP_HOST']] != 'ox') {

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
    $config['site.site_definition.self']['settings']['send_destinations'] = "https://ox.lndo.site/api/site/data?api-key=9f6dfc256451638821d1c88f46ea659d";
  }
}
