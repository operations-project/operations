<?php

namespace Drush\Commands\drush_behat_params;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Composer\Composer;
use Drush\Commands\DrushCommands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Drush\Drush;
use Symfony\Component\Yaml\Yaml;

/**
 *
 */

class BehatCommands extends DrushCommands implements CustomEventAwareInterface
{
  use CustomEventAwareTrait;

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
    'behat_command' => '',
  ])
  {
    global $_composer_bin_dir;

    if (empty($this->input()->getOption('behat_command'))) {
      $default_command = $_composer_bin_dir . '/behat';
      $this->input()->setOption('behat_command', $default_command);
    }

    // Make sure Behat base_url has http in front. Drush works with or without it, but behat needs it to work.
    $uri = $this->commandData->options()['uri'];
    $uri = str_starts_with($uri, 'http')? $uri: 'http://' . $uri;

    // The BEHAT_PARAMS environment variable.
    // Options set in behat.yml will override these.
    $behat_params = [
      "extensions" => [
        "Drupal\\MinkExtension" => [
          "base_url" => $uri,
        ],
        "Drupal\\DrupalExtension" => [
          "drupal" => [
            "drupal_root" => $this->commandData->options()['root']
          ],
          "drush" => [
            "alias" =>  Drush::aliasManager()->getSelf()->name(),
          ]
        ]
      ]
    ];

    $env = [
      "BEHAT_PARAMS" => json_encode($behat_params),
    ];

    // Run in vendor/..
    $reflection = new \ReflectionClass(ClassLoader::class);
    $cwd = dirname(dirname(dirname($reflection->getFileName())));;
    $command = $this->input()->getOption('behat_command');
    $command .= ' ' . implode(' ', $arguments);

    $this->io()->table(['Drush Alias', 'URL', 'Root'], [[
      Drush::aliasManager()->getSelf()->name(),
      $this->commandData->options()['uri'],
      $this->commandData->options()['root'],
    ]]);
    $this->io()->table([],[[Yaml::dump($behat_params, 10, 2)]]);
    $this->io()->title("Running <comment>$command</comment> in <comment>$cwd</comment> with the above BEHAT_PARAMS environment variable...");

    $exit = $this->processManager()->shell($command, $cwd, $env)->run(function ($type, $buffer) {
      echo $buffer;
    }
    );
    return $exit;
  }
}
