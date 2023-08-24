<?php

namespace Drupal\site\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines site_property annotation object.
 *
 * @Annotation
 */
class SiteProperty extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The property name to apply to the site.
   *
   * @var string
   */
  public $name;

  /**
   * If true, property will be loaded for remote sites.
   *
   * @var bool
   */
  public $remote;

  /**
   * If true, hide the property on view pages.
   *
   * @var bool
   */
  public $hidden;

  /**
   * The default value to use if no value found.
   *
   * @var string
   */
  public $default_value;

  /**
   * A list of bundle classes to attach this property to.
   *
   * @var string
   */
  public $site_bundles;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
