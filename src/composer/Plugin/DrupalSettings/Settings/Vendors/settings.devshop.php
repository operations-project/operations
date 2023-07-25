<?php
/**
 * @file settings.devsohp.php
 *
 * This file is included by the file sites/X.com/settings.php that is written by devshop.
 *
 * This include is added so that devshop reads the globally used sites/default/settings.php.
 */
include "settings.php";

# Set ENV vars for site.module
putenv('DRUPAL_SITE_HOST_PROVIDER=devshop');
putenv('DRUPAL_ENV=dev');
