<?php

namespace Drupal\site\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines site_action annotation object.
 *
 * @Annotation
 */
class SiteAction extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * Whether or not to show this action in the Site Entity Operations menu widget.
   *
   * @var bool
   */
  public $site_entity_operation;
}
