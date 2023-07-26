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
   * @description Run bin/behat with BEHAT_PARAMS set from the drush information.
   *
   * Additional arguments are passed to behat command line.
   * To pass options to behat, add "--" and then the behat options.
   *
   * @command behat
   * @param $arguments A list of arguments and options to pass to behat. When using options, put after "--" so the options are passed to behat and not drush.
   * @usage drush behat
   *   Run all behat tests.
   * @usage drush behat -- --format=progress
   *   Run all behat tests using "progress" format.
   * @usage drush behat -- --help
   *   Run bin/behat --help
   * @usage drush behat -- -dl
   *   Get a list of step definitions
   * @usage drush behat -- --story-syntax
   *   Print out a sample test.
   * @usage drush behat features/content
   *   Run bin/behat features/content
   */
  public function behat(array $arguments, $options = [
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
    $command .= ' ' . implode(' ', $arguments);

    $this->logger()->notice("Detected URL and root from Drush:");
    $this->logger()->notice($this->commandData->options()['uri']);
    $this->logger()->notice($this->commandData->options()['root']);
    $this->logger()->notice($this->siteAliasManager()->getSelf()->name());
    $this->logger()->notice("Cwd: " . $cwd );
    $this->logger()->notice("Command: " . $command );

    $exit = $this->processManager()->shell($command, $cwd, $env)->run(function ($type, $buffer) {
      echo $buffer;
    }
    );
    return $exit;
  }
}