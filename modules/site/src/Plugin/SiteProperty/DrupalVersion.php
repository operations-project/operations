<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\site\SitePropertyPluginBase;

/**
 * Plugin implementation of the site_property.
 *
 * @SiteProperty(
 *   id = "drupal_version",
 *   name = "drupal_version",
 *   label = @Translation("Drupal Version"),
 *   description = @Translation("The version of Drupal the site is running.")
 * )
 */
class DrupalVersion extends SitePropertyPluginBase {

  public function value() {
    return \Drupal::VERSION;
  }

}
