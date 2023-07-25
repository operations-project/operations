<?php
/**
 * @file autoload.php
 *
 * This file is included very early in calls to this site.
 *
 * This allows us to alter things like DRUSH_OPTIONS_URI before drush bootstraps.
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
