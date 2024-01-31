<?php

namespace Drupal\task;

/**
 * Interface for task_command plugins.
 */
interface TaskCommandInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

}
