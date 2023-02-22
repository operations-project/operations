<?php

namespace Drupal\devshop_task\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines devshop_task_type annotation object.
 *
 * @Annotation
 */
class DevshopTaskType extends Plugin {

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
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The command to run for this plugin
   *
   * @var string
   *
   * @ingroup plugin_translatable
   */
  public $command;

}
