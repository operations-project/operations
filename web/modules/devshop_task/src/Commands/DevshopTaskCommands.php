<?php

namespace Drupal\devshop_task\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\node\Entity\Node;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Drush\Exceptions\CommandFailedException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class DevshopTaskCommands extends DrushCommands {

  public const TASK_QUEUED = 3;
  public const TASK_RUNNING = 2;
  public const TASK_FAILURE = 1;
  public const TASK_SUCCESS = 0;

  /**
   * Run Task
   *
   * @param $id
   *   The ID of the task to run.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   * @option option-name
   *   Description
   * @usage task:run 123
   *   Enter the node ID of the task to run.
   *
   * @command task:run
   * @aliases t
   */
  public function taskRun($id, $options = ['debug' => false]) {

    $task = Node::load($id);
    if ($task) {

      if ($task->field_state->value == self::TASK_RUNNING) {
        $this->logger()->warning(dt('Task already running: !summary [!link].', [
          '!summary' => $task->getTitle(),
          '!link' => $task->toUrl()->setAbsolute(true)->toString(),
        ]));
      }
      elseif ($task->field_state->value == self::TASK_FAILURE) {
        $this->logger()->error(dt('Task already failed: !summary [!link].', [
          '!summary' => $task->getTitle(),
          '!link' => $task->toUrl()->setAbsolute(true)->toString(),
        ]));
      }
      elseif ($task->field_state->value == self::TASK_SUCCESS) {
        $this->logger()->success(dt('Task already succeeded: !summary [!link].', [
          '!summary' => $task->getTitle(),
          '!link' => $task->toUrl()->setAbsolute(true)->toString(),
        ]));
      }
      elseif ($task->field_state->value == self::TASK_QUEUED) {
        $this->logger()->success(dt('Found Task: !summary [!link].', [
          '!summary' => $task->getTitle(),
          '!link' => $task->toUrl()->setAbsolute(true)->toString(),
        ]));

        $this->taskRunExecute($task);
      }
      else {
        # @TODO: (maybe?) Print constant names too.
        throw new CommandFailedException(dt('Task field_state value is not found in DevShopTaskCommands. Possible states are: 0,1,2,3'));
      }
    }
    else {
      throw new CommandFailedException(dt('No task found with that ID.'));
    }
  }

  private function taskRunExecute($task) {
    $args = explode(' ', $task->field_command->value);
    $process = Drush::process($args);

    try {
      $process->mustRun(function ($type, $buffer) {
        echo $buffer;
      });
    }
    catch (ProcessFailedException $exception) {
      throw $exception;
    }
  }

  /**
   * An example of the table output format.
   *
   * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @field-labels
   *   group: Group
   *   token: Token
   *   name: Name
   * @default-fields group,token,name
   *
   * @command devshop_task:token
   * @aliases token
   *
   * @filter-default-field name
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   */
  public function token($options = ['format' => 'table']) {
    $all = \Drupal::token()->getInfo();
    foreach ($all['tokens'] as $group => $tokens) {
      foreach ($tokens as $key => $token) {
        $rows[] = [
          'group' => $group,
          'token' => $key,
          'name' => $token['name'],
        ];
      }
    }
    return new RowsOfFields($rows);
  }
}
