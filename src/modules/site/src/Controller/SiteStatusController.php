<?php

namespace Drupal\site\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\site\Entity\SiteDefinition;
use Drupal\site\Entity\SiteEntity;

/**
 * Returns responses for Site routes.
 */
class SiteStatusController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $site_definition = SiteDefinition::load('self');
    $build['view'] = $site_definition->view();

    $site_entity = SiteEntity::loadSelf();

//    $build['status']['site'] = [
//      '#title' => t('Site Information'),
//      '#type' => 'details',
//      '#open' => true,
//      '#weight' => 'information',
//      'site' => $site_entity->view(),
////    ];
//    $build['status']['config']['#weight'] = 10;
//    $build['status']['states']['#weight'] = 20;

//    dsm($build);
    return $build;
  }

}
