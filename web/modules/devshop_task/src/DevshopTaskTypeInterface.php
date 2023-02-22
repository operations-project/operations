<?php

namespace Drupal\devshop_task;

/**
 * Interface for devshop_task_type plugins.
 */
interface DevshopTaskTypeInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

}
