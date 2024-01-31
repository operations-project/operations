<?php

namespace Drupal\task\Plugin\TaskCommand;

use Drupal\task\TaskCommandPluginBase;

/**
 * Plugin implementation of the task_command.
 *
 * @TaskCommand(
 *   id = "pwd",
 *   label = @Translation("Whoami"),
 *   description = @Translation("Runs the whoami command."),
 *   command = "pwd"
 * )
 */
class Pwd extends TaskCommandPluginBase {

}
