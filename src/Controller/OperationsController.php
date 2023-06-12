<?php

namespace Drupal\operations\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Operations routes.
 */
class OperationsController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
