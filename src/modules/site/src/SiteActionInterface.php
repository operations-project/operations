<?php

namespace Drupal\site;

/**
 * Interface for site_action plugins.
 */
interface SiteActionInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

}
