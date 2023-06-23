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
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\SiteEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Site routes.
 */
class SiteController extends ControllerBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, EntityRepository $entity_repository) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
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
    $site_view_controller = new EntityViewController($this->entityTypeManager(), $this->renderer);
    $page = $site_view_controller->view($site_revision);
//    unset($page['sites'][$site_revision->id()]['#cache']);
    return $page;
  }
}
