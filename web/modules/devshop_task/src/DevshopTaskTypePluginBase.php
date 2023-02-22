<?php

namespace Drupal\devshop_task;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for devshop_task_type plugins.
 */
abstract class DevshopTaskTypePluginBase extends PluginBase implements DevshopTaskTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function command() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['command'];
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['description'];
  }

}
