<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\site\SitePropertyPluginBase;

/**
 * Plugin implementation of the site_property.
 *
 * @SiteProperty(
 *   id = "php_version",
 *   name = "php_version",
 *   label = @Translation("PHP Version"),
 *   description = @Translation("The version of PHP the site is running.")
 * )
 */
class PhpVersion extends SitePropertyPluginBase {

  public function value() {
    return phpversion();
  }

}
