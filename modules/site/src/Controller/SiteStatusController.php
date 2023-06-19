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

    $site_definition = SiteDefinition::load('self');
    $build['content'] = $site_definition->view();

    $build['save_entity_form'] = \Drupal::formBuilder()->getForm('Drupal\site\Form\SiteDefinitionEntitySaveForm');

    return $build;
  }

}
