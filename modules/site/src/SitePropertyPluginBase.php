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
   * @var mixed A description of the property.
   */
  protected $description;

  /**
   * @var bool Hide the property on view pages.
   */
  protected $hidden;

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
  public function hidden() {
    return $this->pluginDefinition['hidden'] ?? false;
  }

  /**
   * {@inheritdoc}
   */
  public function value() {
    return $this->value ?? $this->pluginDefinition['default_value'];
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->description ?? $this->pluginDefinition['description'];
  }

}
