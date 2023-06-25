<?php

namespace Drupal\site;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for site_property plugins.
 */
abstract class SitePropertyPluginBase extends PluginBase implements SitePropertyInterface {

  /**
   * @var mixed The name of this property.
   */
  protected $name;

  /**
   * @var mixed The value of this property.
   */
  protected $value;

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
  public function name() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function value() {
    return $this->value ?? $this->pluginDefinition['default_value'];
  }

}
