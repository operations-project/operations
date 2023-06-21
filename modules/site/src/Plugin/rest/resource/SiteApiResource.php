<?php

namespace Drupal\site\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Represents Site Entities as resources.
 *
 * @RestResource (
 *   id = "site_api",
 *   label = @Translation("Site API"),
 *   uri_paths = {
 *     "canonical" = "/api/site/{id}",
 *     "create" = "/api/site/create"
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
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest')
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
    $request = \Drupal::request();

    if (isset($data['test']) && $data['test']) {
      $headers['Message'] = $this->t('Test POST was successful.');
      $this->logger->notice($this->t('Remote Test Received from IP :ip',[
        ':ip' => $request->getClientIp(),
      ]));
      return new ModifiedResourceResponse($data, 200, $headers);
    }
    elseif (isset($data['report']) && $data['report']) {
      $entity_data = $data['report'];

      // currentUser should be the REST API Authenticated user (Usually API key.))
      $entity_data['uid'] = \Drupal::currentUser()->id();

      $report_entity =  \Drupal::entityTypeManager()
        ->getStorage('site_audit_report')
        ->create($entity_data)
      ;
      $report_entity->save();
      $url =  $report_entity->toUrl('canonical', [
        'absolute' => TRUE,
      ])->toString();
      $this->logger->notice($this->t('Remote Report Received from :ip: :label - :url', [
        ':label' => $report_entity->label(),
        ':url' => $url,
        ':ip' => $request->getClientIp(),
      ]));

      # @TODO: Allow modules to add to responses.
      $headers = [
        'Message' => $this->t('Report Received.'),
        'ReportUri' => $url,
      ];
      $response = new ModifiedResourceResponse($data, 200, $headers);
      $response->headers->set('report', json_encode($data['report']));

      // Invoke hook_site_audit_server_response.
      \Drupal::moduleHandler()->alter('site_audit_server_response', $response, $report_entity);

      return $response;

    }
    else {
      $headers['Message'] = $this->t('No "test" or "report" found in POST data: $data = :var', [
        ':var' => var_export($data),
      ]);
      return new ModifiedResourceResponse($data, 400, $headers);
    }
  }

  /**
   * Responds to GET requests.
   *
   * @param int $id
   *   The ID of the record.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the record.
   */
  public function get($id) {
    $items = \Drupal::entityTypeManager()
      ->getStorage('site_audit_report') 
      ->load($id)
      ->toArray();
    
    // @TODO: What's the right way?
    print Json::encode($items);
    return;
  }
}
