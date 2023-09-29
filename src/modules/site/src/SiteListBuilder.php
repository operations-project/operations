<?php

namespace Drupal\site;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\site\Entity\SiteType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the site entity type.
 */
class SiteListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;


  /**
   * The site_type route param.
   *
   * @var SiteType
   */
  protected $site_type;

  /**
   * Constructs a new SiteListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    $site_type = \Drupal::routeMatch()->getParameter('site_type');
    $this->site_type = $site_type;
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->sort('changed', 'desc');

    if ($site_type) {
      $query
        ->condition('site_type', $site_type->id())
      ;
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t('There are no @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
    foreach ($this->load() as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
        $build['table']['#rows'][$entity->id(). '-details']['data'] = $entity->siteHistoryTableRowDetails();
        $build['table']['#rows'][$entity->id() . '-details']['data'][0]['colspan'] = count($row['data']);
        $build['table']['#rows'][$entity->id() . '-details']['class'] = [
          'site-revision',
          'site-revision-details',
          'state-' . $entity->stateClass(),
          'color-' . $entity->stateClass(),
        ];
      }
    }
    $total_columns = 0;
    foreach ($build['table']['#rows'] as $row) {
      $total_columns = count($row['data']) > $total_columns? count($row['data']): $total_columns;
    }
    foreach ($build['table']['#rows'] as &$row) {
      if (count($row['data']) == 1) {
        $row['data'][0]['colspan'] = $total_columns;
      }
      else {
        $row['data'] = array_pad($row['data'], $total_columns, '');
      }
    }

    $build['table']['#header'] = array_pad($build['table']['#header'], $total_columns, '');

    $total = $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $build['summary']['#markup'] = $this->t('Total @label_plural: @total', [
      '@total' => $total,
      '@label_plural' => t('sites'),
    ]);

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }

    $build['#attached'] = [
      'library' => ['site/site.admin'],
    ];

    return $build;
  }


  /**
   * @return void
   */
  public function dashboard() {


    $total = $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $build['welcome'] = [
      '#type' => 'fieldset',
      '#title' => t('Welcome'),
      '#markup' => t('Welcome to the Site Module. Create a site or browse your existing sites.'),
    ];



    $build['summary']['#markup'] = $this->t('Total @label_plural: @total', [
      '@total' => $total,
      '@label_plural' => $this->entityType->get('label_plural'),
    ]);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['state'] = $this->t('State');
    $header['site_title'] = $this->t('Site Title');
    $header['site_uri'] = $this->t('URL');
    $header['date'] = $this->t('Log');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [
      'data' => $entity->siteHistoryTableRow(),
      'class' => [
        'site-revision',
        'site-revision-row',
        'state-' . $entity->stateClass(),
        'color-' . $entity->stateClass(),
      ],
    ];
    return $row;
  }
}
