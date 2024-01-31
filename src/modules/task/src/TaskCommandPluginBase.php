<?php

namespace Drupal\task;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for task_command plugins.
 */
abstract class TaskCommandPluginBase extends PluginBase implements TaskCommandInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
