<?php

namespace Drupal\site\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Site routes.
 */
class SiteController extends ControllerBase {

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
