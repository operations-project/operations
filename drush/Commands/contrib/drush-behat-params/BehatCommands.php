<?php

namespace Drush\Commands\drush_behat_params;

use Drupal\Core\Composer\Composer;
use Drush\Commands\DrushCommands;
use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;

/**
 *
 */

class BehatCommands extends DrushCommands implements CustomEventAwareInterface, SiteAliasManagerAwareInterface
{
  use CustomEventAwareTrait;
  use SiteAliasManagerAwareTrait;

  /**
   * Run bin/behat
   *
   * @command behat
   * @usage drush behat
   *   Run behat tests with info from the alias.
   */
  public function behat($options = [
    'behat_command' => 'bin/behat --colors',
  ])
  {
    $behat_params = [
      "extensions" => [
        "Drupal\\MinkExtension" => [
          "base_url" => $this->commandData->options()['uri'],
        ],
        "Drupal\\DrupalExtension" => [
          "drupal" => [
            "drupal_root" => $this->commandData->options()['root']
          ],
          "drush" => [
            "alias" =>  $this->siteAliasManager()->getSelf()->name(),
          ]
        ]
      ]
    ];

    $env = [
      "BEHAT_PARAMS" => json_encode($behat_params),
    ];

    // @TODO: Make configurable
    $cwd = realpath($this->commandData->options()['root'] . '/..');
    $command = $this->input()->getOption('behat_command');

    $this->logger()->notice("Detected URL and root from Drush:");
    $this->logger()->notice($this->commandData->options()['uri']);
    $this->logger()->notice($this->commandData->options()['root']);
    $this->logger()->notice($this->siteAliasManager()->getSelf()->name());
    $this->logger()->notice("Cwd: " . $cwd );
    $this->logger()->notice("Command: " . $command );

    $this->processManager()->shell($command, $cwd, $env)->mustRun(function ($type, $buffer) {
      echo $buffer;
    }
    );
  }
}