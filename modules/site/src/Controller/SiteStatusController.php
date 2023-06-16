<?php

namespace Drupal\site\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\site\Entity\SiteDefinition;

/**
 * Returns responses for Site routes.
 */
class SiteStatusController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $site = SiteDefinition::load('self');
    $build['content'] = $site->view();

    return $build;
  }

}
