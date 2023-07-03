<?php

namespace Drupal\site;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Interface for site_property plugins.
 */
interface SitePropertyInterface {

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
