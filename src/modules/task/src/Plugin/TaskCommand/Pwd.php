<?php

namespace Drupal\task\Plugin\TaskCommand;

use Drupal\task\TaskCommandPluginBase;

/**
 * Plugin implementation of the task_command.
 *
 * @TaskCommand(
 *   id = "pwd",
 *   label = @Translation("Where am I?"),
 *   description = @Translation("Runs the pwd command."),
 *   command = "pwd"
 * )
 */
class Pwd extends TaskCommandPluginBase {

}
