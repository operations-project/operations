<?php

namespace Drupal\site;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;

/**
 * Interface for site_property plugins.
 */
interface SitePropertyInterface extends ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

  /**
   * Return the property value.
   *
   * @return mixed
   */
  public function value();



}
