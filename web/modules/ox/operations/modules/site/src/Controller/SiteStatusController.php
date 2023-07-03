<?php

namespace Drupal\site\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\site\Entity\SiteDefinition;
use Drupal\site\Entity\SiteEntity;

/**
 * Returns responses for Site routes.
 */
class SiteStatusController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $site_definition = SiteDefinition::load('self');
    $build['site_definition'] = [
      '#title' => $this->t('Current Status'),
      '#type' => 'details',
      '#open' => true,
      'table' => $site_definition->view(),
    ];
    $build['site_definition']['save'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Save report'),
      '#open' => true,
      '#collapsible' => false,
      'save' => \Drupal::formBuilder()->getForm('Drupal\site\Form\SiteDefinitionEntitySaveForm'),
    ];
    $build['history'] = [
      '#title' => $this->t('Status History'),
      '#type' => 'details',
      '#open' => true,
      'table' => $this->siteStatusHistoryWidget(),
    ];

    return $build;
  }

  /**
   * Return a table of the last few reports.
   * @return void
   */
  public function siteStatusHistoryWidget() {
    $site_entity = SiteEntity::loadSelf();
    if (!$site_entity) {
      return [];
    }
    $revisions = $site_entity->revisionIds();
    arsort($revisions);
    $revisions = array_slice($revisions, 0, 5);
    $build = [];
    $storage = $this->entityTypeManager()->getStorage('site');

    if ($revisions) {
      $rows = [];
      foreach ($revisions as $vid) {
        $site_revision = $storage->loadRevision($vid);
        $date = $site_revision->revision_timestamp->view([
            'label' => 'hidden'
        ]);
        $state = $site_revision->state->view([
            'label' => 'hidden'
        ]);
        $reason = $site_revision->reason->view([
            'label' => 'hidden',
            'type'=> 'text',
        ]);
        $reason[0]['#format'] = 'basic_html';
        $reason[0]['#prefix'] = '<blockquote>';
        $reason[0]['#suffix'] = '</blockquote>';

        $title = $site_revision->site_title->view([
          'label' => 'hidden',
        ]);

        $row = [];
        $row[] = \Drupal::service('renderer')->render($state);
        $row[] = Link::fromTextAndUrl($site_revision->site_title->value, $site_revision->toUrl('revision'));
        $row[] = $site_revision->get('revision_log')->value;
        $row[] = \Drupal::service('renderer')->render($date);
        $row[] = \Drupal::service('renderer')->render($reason);
        $row[] = $site_revision->get('vid')->value;
        $rows[] = $row;
      }

      $build = [
        '#type' => 'table',
        '#rows' => $rows,
        '#header' => [
          'State',
          'Title',
          'Log',
          'Date',
          'State Reason',
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
