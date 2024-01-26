<?php
/**
 * @file settings.php
 */
$host = 'operations';
if ((bool) getenv('LANDO')) {

  $settings['hash_salt'] = md5(getenv('LANDO_HOST_IP'));
  # To disable forced config like caching, set LANDO_PROD_MODE
  # putenv("LANDO_PROD_MODE=TRUE");

  // Pull the subdomain out and use that as the host.
  if ($_SERVER['HTTP_HOST'] == 'default' && !empty($_SERVER['argv'])) {
    $host = strtr($_SERVER['argv'][1], ['@' => '']);
  }
  else {
    $host = strtr($_SERVER['HTTP_HOST'], ['.lndo.site' => '']);
  }


  if ($host){
    putenv("LANDO_DATABASE_HOST={$host}");
    $settings['file_public_path'] = 'sites/' . $host . '/files';
    $settings['file_private_path'] = 'sites/' . $host . '/files/private';
    $settings['config_sync_directory'] = "../config/{$host}";
  }

  // Site Module settings
  $config['jsonapi.settings']['read_only'] = false;

  if ($host != 'operations') {
    $config['site.settings'] = \Symfony\Component\Yaml\Yaml::parse(<<<YML
  duplicate_handling: unique_urls_per_user
  site_manager:
    api_url: 'http://operations.lndo.site'
    api_key: '123testingkey'
    send_on_save: true
  YML
    );
  }
}


require $app_root . "/../vendor/operations/drupal-settings/Settings/settings.include.php";
