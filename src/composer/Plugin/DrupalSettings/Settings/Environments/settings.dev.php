<?php
/**
 * settings.dev.php
 *
 * This file contains settings that should be set for any production environment on any host.
 *
 * This file is only included if DRUPAL_ENV is set to "dev".
 *
 * @see settings.include.php.
 */

// Include this sites settings.local.php
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include($app_root . '/' . $site_path . '/settings.local.php');
}
// Include drupal's own example.settings.local.php
elseif (file_exists($app_root . "/sites/example.settings.local.php")) {
  include($app_root . "/sites/example.settings.local.php");
}
