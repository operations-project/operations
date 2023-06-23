<?php

namespace Drupal\site\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\site\Entity\SiteDefinition;
use Drupal\site\Entity\SiteEntity;
use http\QueryString;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouteCollection;

/**
 * Exposes simple Site API for getting and saving data.
 *
 * @RestResource (
 *   id = "site_api",
 *   label = @Translation("Site API"),
 *   uri_paths = {
 *     "canonical" = "/api/site/data",
 *     "create" = "/api/site/data"
 *   }
 * )
 *
 * @DCG
 * The plugin exposes key-value records as REST resources. In order to enable it
 * import the resource configuration into active configuration storage. An
 * example of such configuration can be located in the following file:
 * core/modules/rest/config/optional/rest.resource.entity.node.yml.
 * Alternatively you can enable it through admin interface provider by REST UI
 * module.
 * @see https://www.drupal.org/project/restui
 *
 * @DCG
 * Notice that this plugin does not provide any validation for the data.
 * Consider creating custom normalizer to validate and normalize the incoming
 * data. It can be enabled in the plugin definition as follows.
 * @code
 *   serialization_class = "Drupal\foo\MyDataStructure",
 * @endcode
 *
 * @DCG
 * For entities, it is recommended to use REST resource plugin provided by
 * Drupal core.
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class SiteApiResource extends ResourceBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
    );
  }

  /**
   * Responds to POST requests and saves the new record.
   *
   * @param array $data
   *   Data to write into the database.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function post(array $data) {
    $data['data']['received_data'] = $data;
    $data['data']['received_by'] = \Drupal::request()->getClientIP();

    $site_entity = SiteEntity::load($data['site_uuid']);
    if ($site_entity) {
      $site_entity->setNewRevision();
      $site_entity->revision_log = t('Received via API from :from'. [
        ':from' => \Drupal::request()->getClientIP(),
      ]);
      $site_entity->revision_timestamp = \Drupal::time()->getRequestTime();
      foreach ($data as $property => $value) {
        if ($site_entity->hasField($property)) {
          $site_entity->set($property, $value);
        }
      }
    }
    else {
      $site_entity = SiteEntity::create($data);
    }

    $site_entity->no_send = true;
    $site_entity->save();
    return new ModifiedResourceResponse($site_entity->toArray(), 201);
  }

  /**
   * Generate and return a SiteEntity Object.
   *
   * @TODO: Do we need to save a local siteEntity for every GET request?
   * I think it's good because then we can tell what was reported to API clients.
   *
   * @return JsonResponse
   *   The response containing the record.
   */
  public function get() {
    $site_definition = SiteDefinition::load('self');
    $new_site_entity = $site_definition->saveEntity($this->t("Saving site entity via Site API GET request from :ip", [
        ':ip' => \Drupal::request()->getClientIp()
    ]));

    $new_site_entity->data->remote_report_received_from = \Drupal::request()->getClientIp();
    $new_site_entity->data->remote_report_received_query = \Drupal::request()->query->all();
    $new_site_entity->data->remote_site_entity_url = $new_site_entity->toUrl('canonical', [
      'absolute' => true,
    ])->toString();

    foreach (\Drupal::request()->query->all() as $field => $value) {
      if ($field != 'vid' && $new_site_entity->getFieldDefinition($field)) {
        $new_site_entity->set($field, $value);
      }
    }

    if (empty($new_site_entity)) {
      throw new BadRequestHttpException();
    }
    return new JsonResponse($new_site_entity->toArray());
  }
}
