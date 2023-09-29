<?php

namespace Drupal\site;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\filter\Plugin\Filter\FilterUrl;
use Drupal\site\Entity\SiteDefinition;

/**
 * A trait for sharing Site Entity metadata across entities.
 */
trait SiteEntityHistoryTrait {

  /**
   * The list of table headers to show on the Site History page.
   * @return array
   */
  public function siteHistoryTableHeaders() {
    return [
      $this->t('State'),
      $this->t('Site Title'),
      $this->t('URL'),
      $this->t('Log'), // date
    ];
  }

  /**
   * The list of cells in a single Site History row.
   * @return array
   */
  public function siteHistoryTableRow() {
    $revision = $this;
    $row = [];

    // State
    $row[] = [
      'data' => $revision->state->view([
        'label' => 'hidden'
      ]),
    ];

    // Link
    $row[] = [
      'data' => $revision->toLink($revision->title(), 'revision'),
    ];

    // URL
    $row[] = [
      'data' => Link::fromTextAndUrl($revision->site_uri->value, Url::fromUri($revision->site_uri->value)),
    ];

    // Message
    $message = $this->get('revision_log')->view([
      'label' => 'hidden',
      'type' => 'text_default',
    ]);
    if (!empty($message[0])) {
      $message[0] = [
        '#markup' => check_markup($message[0]['#context']['value']),
      ];
    }
    $row[] = [
      'class' => 'log-message',
      'data' => [
        'message' => [
          '#prefix' => '<blockquote>',
          'log' => $message,
          '#suffix' => '</blockquote>',
          '#allowed_tags' => Xss::getHtmlTagList(),
          '#access' => (bool) $this->get('revision_log')->value,
        ],
        'timestamp' => [
          'value' => [
            $revision->revision_timestamp->view([
              'label' => 'hidden',
              'type' => 'timestamp_ago',
              'settings' => [
                'granularity' => 1,
              ],
            ]),
          ],
        ],
      ],
    ];


    return $row;
  }


  /**
   * The list of cells in a single Site History row.
   * @return array
   */
  public function siteHistoryTableRowDetails() {

    // Message
    $message = $this->get('revision_log')->view([
      'label' => 'hidden',
      'type' => 'text_default',
    ]);
    if (!empty($message[0])) {
      $message[0] = [
        '#markup' => check_markup($message[0]['#context']['value']),
      ];
    }

    $row[] = [
      'data' => [
        'datestamp' => [
          '#prefix' => '<em>',
          '#markup' => \Drupal::service('date.formatter')->format($this->revision_timestamp->value, 'long'),
          '#suffix' => '</em>',
        ],
        'message' => [
            '#prefix' => '<blockquote>',
          'log' => $message,
          '#suffix' => '</blockquote>',
          '#allowed_tags' => Xss::getHtmlTagList(),
          '#access' => (bool) $this->get('revision_log')->value,
        ],
        'reason' => $this->reason->getValue(),
        'config' => site_config_changes_build($this),

      ],
    ];
    return $row;
  }


  /**
   * A build array for the site revisions list page.
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function siteHistory() {
    $site_entity = $this;
    $build['#title'] = $this->t('Site History for %title', ['%title' => $site_entity->label()]);
    $header = $this->siteHistoryTableHeaders();

    $rows = [];
    $storage = \Drupal::entityTypeManager()->getStorage('site');
    $revisions = $site_entity->revisionIds();
    foreach ($revisions as $vid) {
      /** @var SiteInterface $revision */
      $revision = $storage->loadRevision($vid);
      $row = $revision->siteHistoryTableRow();
      $rows[] = [
        'data' => $row,
        'class' => [
          'site-revision',
          'site-revision-row',
          'state-' . $revision->stateClass(),
          'color-' . $revision->stateClass(),
        ],
      ];
      $details_row = $revision->siteHistoryTableRowDetails();
      $details_row[0]['colspan'] = count($row);
      $rows[] = [
        'data' => $details_row,
        'class' => [
          'site-revision',
          'site-revision-details',
          'state-' . $revision->stateClass(),
          'color-' . $revision->stateClass(),
        ],
      ];
    }

    $build['site_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attributes' => ['class' => 'site-revision-table'],
      '#attached' => [
        'library' => ['site/site.admin'],
      ],
    ];

    $build['pager'] = ['#type' => 'pager'];
    return $build;
  }
}
