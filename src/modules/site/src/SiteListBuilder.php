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
//    $this->site_type = \Drupal::routeMatch()->getParameter('site_type');

//    if (!$this->site_type) {
//      return $this->dashboard();
//    }

    $build['table'] = parent::render();
    $build['table']['#empty'] = $this->t('There are no @label yet.', ['@label' => t('sites')]);

    $total = $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $build['summary']['#markup'] = $this->t('Total @label_plural: @total', [
      '@total' => $total,
      '@label_plural' => t('sites'),
    ]);
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
    $header['http_status'] = $this->t('HTTP Code');
    $header['site_title'] = $this->t('Label');
    $header['id'] = $this->t('ID');
    $header['site_uri'] = $this->t('Site URI');
    $header['date'] = $this->t('Last Report');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\site\SiteEntityInterface $entity */
    $state =  $entity->state->view([
      'label' => 'hidden',
    ]);
    $revision_log =  $entity->revision_log->view([
      'label' => 'hidden',
    ]);
    $http_status =  $entity->http_status->view([
      'label' => 'hidden',
    ]);
    $row['state'] = \Drupal::service('renderer')->render($state);
    $row['http_status'] = \Drupal::service('renderer')->render($http_status);
    $row['site_title'] = $entity->toLink(null, 'version_history');
    $row['id'] = $entity->site_uuid->value ?? '';

    $row['site_uri'] = $entity->site_uri->value ? Link::fromTextAndUrl($entity->site_uri->value, Url::fromUri($entity->site_uri->value), [
      'attributes' => ['target' => '_blank'],
    ]) : t('Unknown');
    $date = $entity->revision_timestamp->view([
      'label' => 'hidden',
      'type' => 'timestamp_ago'
    ]);

    if ($entity->reason->value) {
      $reason = $entity->reason->view([
        'label' => 'hidden',
        'type'=> 'text',
      ]);
      $reason[0]['#format'] = 'basic_html';
      $reason[0]['#prefix'] = '<blockquote>';
      $reason[0]['#suffix'] = '</blockquote>';
      $reason['#type'] = 'details';
      $reason['#title'] = t('Reason');

    }
    else {
      $reason = [];
    }
    $log_column['log'] = [
      '#prefix' => '<blockquote><small>',
      '#suffix' => '</small></blockquote>',
      '#access' => !empty($entity->revision_log->getValue()),
      'log' => $revision_log,
    ];
    $log_column['date'] = [
      'date' => $date,
      '#prefix' => '<em>',
      '#suffix' => '</em>',
    ];

    $row['log'] = \Drupal::service('renderer')->render($log_column) ;



    return [
      'data' => $row + parent::buildRow($entity),
      'class' => [
        "color-" . $entity->stateClass()
      ]
    ];
  }

}
