<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\site\SitePropertyPluginBase;

/**
 * Plugin implementation of the site_property.
 *
 * @SiteProperty(
 *   id = "foo",
 *   name = "foo",
 *   default_value = "bar",
 *   label = @Translation("Foo"),
 *   description = @Translation("What Foo.")
 * )
 */
class Foo extends SitePropertyPluginBase {

}
