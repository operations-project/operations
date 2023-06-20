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

    $build['history'] = $this->siteStatusHistoryWidget();

    $build['content'] = $site_definition->view();

    $build['save_entity_form'] = \Drupal::formBuilder()->getForm('Drupal\site\Form\SiteDefinitionEntitySaveForm');

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

        $row = [];
        $row[] = \Drupal::service('renderer')->render($state);
        $row[] = $site_revision->toLink();
        $row[] = \Drupal::service('renderer')->render($date);
        $row[] = $site_revision->vid->value;
        $rows[] = $row;
      }

      $build['history'] = [
        '#type' => 'table',
        '#caption' => $this->t('Status Reports'),
        '#rows' => $rows,
        '#header' => [
          'State',
          'Title',
          'Date',
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

  /**
   * Generates an overview table of older revisions of a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node object.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function revisionOverview(NodeInterface $node) {
    $langcode = $node->language()->getId();
    $langname = $node->language()->getName();
    $languages = $node->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $node_storage = $this->entityTypeManager()->getStorage('node');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $node->label()]) : $this->t('Revisions for %title', ['%title' => $node->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $rows = [];
    $default_revision = $node->getRevisionId();
    $current_revision_displayed = FALSE;

    foreach ($this->getRevisionIds($node, $node_storage) as $vid) {
      /** @var \Drupal\node\NodeInterface $revision */
      $revision = $node_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
            '#theme' => 'username',
            '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->revision_timestamp->value, 'short');

        // We treat also the latest translation-affecting revision as current
        // revision, if it was the default revision, as its values for the
        // current language will be the same of the current default revision in
        // this case.
        $is_current_revision = $vid == $default_revision || (!$current_revision_displayed && $revision->wasDefaultRevision());
        if (!$is_current_revision) {
          $link = Link::fromTextAndUrl($date, new Url('entity.node.revision', ['node' => $node->id(), 'node_revision' => $vid]))->toString();
        }
        else {
          $link = $node->toLink($date)->toString();
          $current_revision_displayed = TRUE;
        }

        $row = [];
        $column = [
            'data' => [
                '#type' => 'inline_template',
                '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
                '#context' => [
                    'date' => $link,
                    'username' => $this->renderer->renderPlain($username),
                    'message' => ['#markup' => $revision->revision_log->value, '#allowed_tags' => Xss::getHtmlTagList()],
                ],
            ],
        ];
        // @todo Simplify once https://www.drupal.org/node/2334319 lands.
        $this->renderer->addCacheableDependency($column['data'], $username);
        $row[] = $column;

        if ($is_current_revision) {
          $row[] = [
              'data' => [
                  '#prefix' => '<em>',
                  '#markup' => $this->t('Current revision'),
                  '#suffix' => '</em>',
              ],
          ];

          $rows[] = [
              'data' => $row,
              'class' => ['revision-current'],
          ];
        }
        else {
          $links = [];
          if ($revision->access('revert revision')) {
            $links['revert'] = [
                'title' => $vid < $node->getRevisionId() ? $this->t('Revert') : $this->t('Set as current revision'),
                'url' => $has_translations ?
                    Url::fromRoute('node.revision_revert_translation_confirm', ['node' => $node->id(), 'node_revision' => $vid, 'langcode' => $langcode]) :
                    Url::fromRoute('node.revision_revert_confirm', ['node' => $node->id(), 'node_revision' => $vid]),
            ];
          }

          if ($revision->access('delete revision')) {
            $links['delete'] = [
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute('node.revision_delete_confirm', ['node' => $node->id(), 'node_revision' => $vid]),
            ];
          }

          $row[] = [
              'data' => [
                  '#type' => 'operations',
                  '#links' => $links,
              ],
          ];

          $rows[] = $row;
        }
      }
    }

    $build['node_revisions_table'] = [
        '#theme' => 'table',
        '#rows' => $rows,
        '#header' => $header,
        '#attached' => [
            'library' => ['node/drupal.node.admin'],
        ],
        '#attributes' => ['class' => 'node-revision-table'],
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }
}
