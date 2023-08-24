<?php
namespace Drupal\site_manager\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends \Drupal\field_ui\Routing\RouteSubscriber {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    parent::alterRoutes($collection);

    $entity_type_id = 'site_definition';
    $options = [];
    $options['parameters']['site_type'] = [
      'type' => 'entity:site_type',
    ];
    $options['_field_ui'] = TRUE;

    $route = new Route(
      "/admin/structure/site_types/manage/{site_type}/fields",
      [
        '_controller' => '\Drupal\field_ui\Controller\FieldConfigListController::listing',
        '_title' => 'Manage fields',
        'entity_type_id' => 'site',
        'bundle' => 'site_type',
      ],
      ['_permission' => 'administer site_type fields'],
      $options
    );
    $collection->add("entity.site.field_ui_fields", $route);

  }
}
