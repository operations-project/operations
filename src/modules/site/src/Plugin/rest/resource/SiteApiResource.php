<?php

namespace Drupal\site\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\jsonapi\Serializer\Serializer;
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
   * @return ResourceResponse
   *   The HTTP response object.
   */
  public function post($data) {

    if (!empty($data['test'])) {
      return new JsonResponse(["message" => t("Successfully connected to :title at :url.", [
        ':title' => SiteEntity::getSitetitle(),
        ':url' =>  SiteEntity::getUri(),
      ])]);
    }

    unset($data['sid']);

    // Check for action requests
    \Drupal::logger('site_api')->warning('POST received:' . print_r($data,1));
    if (!empty($data['action'])) {
      $site_entity = SiteEntity::saveRevision(t('Action requested (:action) via API from :from', [
          ':from' => \Drupal::request()->getClientIP(),
          ':action' => $data['action'],
        ]), false);
      return new ModifiedResourceResponse($site_entity, 201);
    }

    $site_entity = SiteEntity::loadBySiteUrl($data['site_uri']);
    $data['data']['site_manager_response']['received_data'] = $data;
    $data['data']['site_manager_response']['received_from'] = \Drupal::request()->getClientIP();

    if ($site_entity) {
      $data['data']['site_manager_site_url'] = $site_entity->toUrl('canonical', ['absolute' => true])->toString();
      $site_entity->setNewRevision();
      $site_entity->revision_log = t('Received via API from :from'. [
        ':from' => \Drupal::request()->getClientIP(),
      ]);
      $site_entity->revision_timestamp = \Drupal::time()->getRequestTime();
      foreach ($data as $property => $value) {
        if ($site_entity->hasField($property) && $property != 'sid') {
          $site_entity->set($property, $value);
        }
      }
    }
    else {
      $site_entity = SiteEntity::create($data);
    }

    $site_entity->no_send = true;

    // EXAMPLE: Set things here to change the entity that is sent back.
    // $site_entity->set('state', SiteDefinition::SITE_ERROR);
    // $site_entity->set('reason', ['#markup' => "Site validation failed: because."]);

    $site_entity->save();
    return new ModifiedResourceResponse($site_entity, 201);
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
    $site = \Drupal::service('site.self')->getEntity();
    \Drupal::service('site.self')->prepareEntity($site);

    /** @var Serializer $serializer */
//    $serializer =  \Drupal::service('serializer');
//    $data = $serializer->serialize($site, 'json');
    return new JsonResponse($site->toArray(), 201);
  }
}
