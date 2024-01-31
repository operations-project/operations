<?php

namespace Drupal\task\Plugin\TaskCommand;

use Drupal\task\TaskCommandPluginBase;

/**
 * Plugin implementation of the task_command.
 *
 * @TaskCommand(
 *   id = "whoami",
 *   label = @Translation("Whoami"),
 *   description = @Translation("Runs the whoami command."),
 *   command = "whoami"
 * )
 */
class Whoami extends TaskCommandPluginBase {

}
