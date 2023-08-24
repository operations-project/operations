<?php

namespace Drupal\site\Controller;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\jsonapi\Controller\EntityResource;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\SiteSelf;
use http\Client\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Site routes.
 */
class SiteApiController extends ControllerBase {

  /**
   * The site.self service.
   *
   * @var \Drupal\site\SiteSelf
   */
  protected $self;

  /**
   * The controller constructor.
   *
   * @param \Drupal\site\SiteSelf $self
   *   The site.self service.
   */
  public function __construct(SiteSelf $self) {
    $this->self = $self;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('site.self')
    );
  }

  /**
   * Builds the response.
   */
  public function self() {
    $site = $this->self->getEntity();
    if (!$site->id()) {
      $site->save();
    }
    $response = [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'data' => $site->toJsonApiArray(),
    ];

    $requester = \Drupal::request()->headers->get('requester');
    $site = SiteEntity::loadBySiteUrl('http://'.$requester);

    $response['requester']['hostname'] = $requester;
    $response['requester']['site_entity'] = null;
    if ($site) {
      $response['requester']['site_entity'] = $site->toJsonApiArray();
    }

    return JsonResponse::create($response);
  }

  /**
   * SiteAction JSON API Endpoint: /jsonapi/action/user_login
   */
  public function action(string $plugin_id) {
    $site = $this->self->getEntity();
    try {
      $plugin = \Drupal::service('plugin.manager.site_action')->createInstance($plugin_id, [
        'site' => $site,
      ]);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException();
    }

    $response = [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'data' => $site->toJsonApiArray(),
    ];

    $requester = \Drupal::request()->headers->get('requester');
    $site = SiteEntity::loadBySiteUrl($requester);
    
    $response['requester']['hostname'] = $requester;

    if ($site) {
      $response['requester']['site_entity'] = $site->toJsonApiArray();
    }

    // Add link
    $plugin->apiResponse($response);
    return JsonResponse::create($response);
  }
}
