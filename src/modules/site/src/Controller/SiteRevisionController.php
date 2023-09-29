<?php

namespace Drupal\site\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\site\Entity\SiteDefinition;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\SiteEntityInterface;
use Drupal\site\SiteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Site routes.
 */
class SiteRevisionController extends EntityController {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  public function __construct(EntityTypeManagerInterface $entity_type_manager,EntityTypeBundleInfoInterface $entity_type_bundle_info, DateFormatterInterface $date_formatter, RendererInterface $renderer, EntityRepository $entity_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('entity.repository')
    );
  }

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

  /**
   * Displays a site revision.
   *
   * @param \Drupal\site\SiteEntityInterface $site_revision
   *   The node revision.
   *
   * @return array
   *   An array suitable for \Drupal\Core\Render\RendererInterface::render().
   */
  public function revisionShow(SiteEntityInterface $site_revision) {
    $site_view_controller = new EntityViewController($this->entityTypeManager, $this->renderer);
    $page = $site_view_controller->view($site_revision);
//    unset($page['sites'][$site_revision->id()]['#cache']);
    return $page;
  }

  /**
   * Site Revisions list page.
   * @return array
   *    An array as expected by \Drupal\Core\Render\RendererInterface::render().
   *
   */
  public function revisionHistory(SiteInterface $site = null) {
    return $site ? $site->siteHistory() : SiteEntity::loadSelf()->siteHistory();
  }
}
