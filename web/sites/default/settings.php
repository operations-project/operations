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


if (empty($projects[$_SERVER['HTTP_HOST']])) {
  $_SERVER['HTTP_HOST'] = 'ox.lndo.site';
}

$project = $projects[$_SERVER['HTTP_HOST'] ?: 'ox'] ?? 'ox';
$settings['config_sync_directory'] = "../config/{$project}";
$settings['hash_salt'] = '';

if ((bool) getenv('LANDO')) {
  $settings['hash_salt'] = md5(getenv('LANDO_HOST_IP'));
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
    $settings['file_private_path'] = 'sites/' . $host . '/files/private';
  }
  else {
    $host = 'default';
    $settings['file_public_path'] = 'sites/' . $host . '/files';
    $settings['file_private_path'] = 'sites/' . $host . '/files/private';
  }

  $uuids = [
    'ox' => 'd3c85e16-e0db-49f1-b7fd-f13e84f3b9dd',
    'mercury' => 'daaa81bd-5f27-4152-a209-511e31368848',
    'mars' => 'site.mars',
    'venus' => 'site.venus',
  ];
  $config['system.site']['uuid'] = $uuids[$host] ?: random_bytes(10);
}

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
    $config['site.site_definition.self']['settings']['send_destinations'] = "https://ox.lndo.site/api/site/data?api-key=61455cf19e740372b155ee6c380d3d7a";
  }
}

require $app_root . "/../vendor/drupal-operations/drupal-settings/Settings/settings.include.php";
