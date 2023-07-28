<?php

namespace Drupal\site;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
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
    $build['table'] = parent::render();

    $total = $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->sort('changed', 'DESC')
      ->execute();

    $build['summary']['#markup'] = $this->t('Total sites: @total', ['@total' => $total]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['state'] = $this->t('State');
    $header['site_title'] = $this->t('Site Title');
    $header['drupal_version'] = $this->t('Drupal');
    $header['php_version'] = $this->t('PHP');
    $header['site_title'] = $this->t('Site Title');
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
    $drupal_version =  $entity->drupal_version->view([
      'label' => 'hidden',
    ]);
    $php_version =  $entity->php_version->view([
      'label' => 'hidden',
    ]);
    $revision_log =  $entity->revision_log->view([
      'label' => 'hidden',
    ]);
    $row['state'] = \Drupal::service('renderer')->render($state);
    $row['site_title'] = $entity->toLink(null, 'version_history');
    $row['drupal_version'] =  \Drupal::service('renderer')->render($drupal_version);
    $row['php_version'] =  \Drupal::service('renderer')->render($php_version);
    $row['id'] = $entity->site_uuid->value;
    $row['site_uri'] = Link::fromTextAndUrl($entity->site_uri->value, Url::fromUri($entity->site_uri->value), [
      'attributes' => ['target' => '_blank'],
    ]);
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
        "color-" . $entity->getStateClass()
      ]
    ];
  }

}
