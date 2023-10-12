<?php

namespace Drupal\site\Drush\Commands;

use _PHPStan_c900ee2af\Symfony\Component\Console\Exception\CommandNotFoundException;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Url;
use Drupal\site\Entity\SiteEntity;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\CommandFailedException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;

/**
 * Site module drush commands.
 */
class SiteCommands extends DrushCommands {

  protected $siteEntity;

  /**
   * Constructs a SiteCommands object.
   */
  public function __construct(

  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(

    );
  }

  public function setOutput(OutputInterface $output)
  {
    $output->getFormatter()->setStyle('ok', new OutputFormatterStyle('green',null, ['bold']));
    $output->getFormatter()->setStyle('warning', new OutputFormatterStyle('yellow',null, ['bold']));
    $output->getFormatter()->setStyle('error', new OutputFormatterStyle('red',null, ['bold']));
    $output->getFormatter()->setStyle('site_info', new OutputFormatterStyle('cyan',null, ['bold']));
    $output->getFormatter()->setStyle('processing', new OutputFormatterStyle('white',null, ['bold']));
    parent::setOutput($output);
  }

  /**
   * View or set the state of site entities.
   *
   * @param $site
   *   The site to view or update. Leave blank to use the current site. Use "all" to show all sites.
   * @param array $options
   * @option state
   *   The desired state of the site. Valid options are: ok, warn, error, processing
   * @option reason
   *   A string describing the reason for this state.
   *
   * @usage site:state
   *   Show status of the current site.
   *
   * @usage site:state all
   *   Show status of all sites.
   *
   * @usage site:state google.com
   *   Show status of the site with URL google.com
   *
   * @usage site:state --state=ok
   *   Set the state of the current site to "ok"
   *
   * @usage site:state --state=error --reason-label="Tests Failed" --reason="See test runner for results."
   *   Set the current site state to error, including a reason and reason label.
   *
   * @command site:state
   * @aliases state
   */
  public function siteState($site = null, $options = [
    'state' => null,
    'reason' => '',
    'reason-label' => '',
    'revision-log' => 'Site state updated via drush',
  ]) {

    // If site argument was specified, load that.
    if ($site == 'all') {
      return $this->statusTableAll();
    }
    elseif ($site) {
      $url = $site;
      $entity = SiteEntity::loadBySiteUrl($url);
    }
    else {
      $url = $this->input()->getOption('uri');
      $entity = \Drupal::service('site.self')->getEntity();
    }

    // Create site if it doesn't exist.
    // @TODO: This code is SiteAboutController. Make it a method.
    if (empty($entity->id())) {
      try {
        $entity = \Drupal::service('site.self')->prepareEntity()->saveEntity(t('Report saved by @user via Save Report form.', [
          '@user' => \Drupal::currentUser()->getAccount()->getDisplayName(),
        ]));
        if ($entity->sent) {
          \Drupal::messenger()->addStatus(t('Site data updated and sent.'));
        }
        else {
          \Drupal::messenger()->addStatus(t('Site data updated.'));
        }
      }
      catch (AccessDeniedException $e) {
        \Drupal::messenger()->addError($e->getMessage());
        \Drupal::messenger()->addError(t('Access was denied when sending site data. Check the <a href=":link">:link_text</a> and try again.', [
          ':link' => Url::fromRoute('site.advanced', [], ['fragment' => 'edit-site-manager'])->toString(),
          ':link_text' => t('Site Manager Connection API Key'),
        ]));
      }
      catch (\Exception $e) {
        \Drupal::messenger()->addError($e->getMessage());
      }
    }

    $this->siteEntity = $entity;

    if (empty($entity)) {
      throw new CommandFailedException(dt('Site ":site" not found.', [
        ':site' => $url,
      ]));
    }

    // If --state not specified, just print status.
    if (empty($options['state'])) {
      return;
    }
    // If --state was specified, set it.
    else {
      $this->statusTable();

      try {
        // Save a new site with the new state value.
        $entity
          ->setRevisionLogMessage($options['revision-log'] . ' | ' . \Drupal::service('date.formatter')->format(time()));

        // Prepare values, then set set.
        $entity = \Drupal::service('site.self')->setEntity($entity)->prepareEntity()->getEntity();

        // Add reason and state, and tell SiteEntity::preSave() not to prepare properties again.
        $entity
          ->skipPrepare()
          ->set('state', SiteEntity::getStateValue($options['state']));

        if (!empty($options['reason'])) {
          $entity->addReason([
            '#type' => 'item',
            '#title' => dt($options['reason-label']),
            '#prefix' => '<pre>',
            '#markup' => dt($options['reason']),
            '#suffix' => '</pre>',
          ]);
        }

        $rows[] = [
          $this->stateIndicator($entity),
          $options['reason-label'],
          $options['reason'],
        ];

        $this->io()->table(['Desired State', 'Reason'], $rows);

        if ($this->io()->confirm('Set desired state?')) {
          $entity->validate();
          $entity->save();

          $message = dt('Site state was set to :state', [
            ':state' => $options['state'],
          ]);
          switch ($options['state']) {
            case 'info':
              $this->io()->block($message, null, 'fg=black;bg=cyan', ' ', true);
              break;

            case 'ok':
              $this->io()->block($message, null, 'fg=black;bg=green', ' ', true);
              break;

            case 'warning':
              $this->io()->block($message, null, 'fg=black;bg=yellow', ' ', true);
              break;

            case 'error':
              $this->io()->block($message, null, 'fg=black;bg=red', ' ', true);
              break;
            case 'processing':
              $this->io()->block($message, null, 'fg=black;bg=white', ' ', true);
              break;


          }

        }
        else {
          throw new CommandFailedException('Cancelled.');
        }

      }
      catch (\Exception $e) {
        throw new CommandFailedException($e->getMessage());
      }

    }
  }

  /**
   * Show site details.
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   */
  private function statusTable($table_title = 'Current State') {
    $entity = $this->siteEntity;
    $rows[] = [
      $this->stateIndicator($entity),
      $entity->site_uri->getString(),
      $entity->site_title->value,
      $entity->toUrl('canonical', ['absolute' => true])->toString(),
    ];

    $this->io()->table([$table_title, 'URL', 'Title', 'Link'], $rows);
  }

  /**
   * Show site details.
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   */
  private function statusTableAll() {
    $values = [];
    $sites = \Drupal::entityTypeManager()
      ->getStorage('site')
      ->loadByProperties($values);

    foreach ($sites as $entity) {
      $rows[] = [
        $this->stateIndicator($entity),
        $entity->site_uri->getString(),
        $entity->site_title->value,
        $entity->toUrl('canonical', ['absolute' => true])->toString(),
      ];
    }

    $this->io()->table(['Current State', 'URL', 'Title', 'Link'], $rows);
  }

  function stateIndicator($entity) {
    $state = $entity->stateId();

    // Use "site_info" to not conflict with info style.
    $state = $state == 'info' ? $state = 'site_info': $state;
    $output = "<$state>" . $entity->stateIcon() . ' ' . $entity->stateName() . "</$state>";
    return $output;
  }
}
