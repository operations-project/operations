<?php

namespace Drupal\task\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines task_command annotation object.
 *
 * @Annotation
 */
class TaskCommand extends Plugin {

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
   * The command to run.
   *
   * @var string
   */
  public $command;

}
