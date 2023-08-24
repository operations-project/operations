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
   * Return a table of the last few reports.
   * @return void
   */
  public function siteStatusHistoryWidget() {
    $site_entity = \Drupal::routeMatch()->getParameter('site');
    if (!$site_entity) {
      $site_entity = SiteEntity::loadSelf();
      if (!$site_entity) {
        \Drupal::messenger()->addWarning(t('There are no site reports yet.'));
        return $this->redirect('site.about');
      }
    }
    $site_entity->isLatestRevision();
    $revisions = $site_entity->revisionIds();
    arsort($revisions);
    // @TODO: Implement a pager.
    // $revisions = array_slice($revisions, 0, 5);
    $build = [];
    $storage = \Drupal::entityTypeManager()->getStorage('site');

    if ($revisions) {
      $rows = [];
      foreach ($revisions as $vid) {
        $site_revision = $storage->loadRevision($vid);
        $date = $site_revision->isLatestRevision()?
          $site_revision->changed->view([
            'label' => 'hidden',
            'type' => 'timestamp_ago'
          ]):
          $site_revision->revision_timestamp->view([
            'label' => 'hidden',
            'type' => 'timestamp_ago'
          ]);

        $state = $site_revision->state->view([
          'label' => 'hidden'
        ]);

        $state['#attributes']['class'][] = $site_revision->stateClass();

        $reason = [];
        $reason_value = $site_revision->reason->getValue();
        if ($reason_value) {
          $reason['#type'] = 'details';
          $reason['#title'] = t('Reason');
          $reason['reason'] = $reason_value;
        }
        else {
          $reason = [];
        }

        $drupal_version = $site_revision->drupal_version->view([
          'label' => 'hidden',
        ]);

        $php_version = $site_revision->php_version->view([
          'label' => 'hidden',
        ]);

        $http_status =  $site_revision->http_status->view([
          'label' => 'hidden',
        ]);
        $row = [];
        $row[] = \Drupal::service('renderer')->render($state);
        $row[] = \Drupal::service('renderer')->render($http_status);
        $row[] = $site_revision->toLink(null, 'revision');
        $row[] = Link::fromTextAndUrl($site_revision->site_uri->value, Url::fromUri($site_revision->site_uri->value), [
          'attributes' => ['target' => '_blank'],
        ]);
        $row[] = \Drupal::service('renderer')->render($drupal_version);
        $row[] = \Drupal::service('renderer')->render($php_version);
        $row[] = \Drupal::service('renderer')->render($date);
        $row[] = \Drupal::service('renderer')->render($reason);
        $row[] = $site_revision->get('revision_log')->value;
        $row[] = $site_revision->get('vid')->value;
        $rows[] = [
          'data' => $row,
          'class' => [
            'color-' . $site_revision->stateClass(),
          ],
          'valign' => 'top',
        ];
      }

      $build = [
        '#type' => 'table',
        '#rows' => $rows,
        '#header' => [
          'State',
          'HTTP Status',
          'Title',
          'URL',
          'Drupal',
          'PHP',
          'Date',
          'State Reason',
          'Log',
          'Report #'
        ],
      ];
    }
    else {
      \Drupal::messenger()->addWarning('No historical reports. Click "Save Site Record" to save a historical report.');
      $build = [];
    }
    return $build;

  }
}
