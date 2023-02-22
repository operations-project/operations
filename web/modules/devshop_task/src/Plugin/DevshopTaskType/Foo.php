<?php

namespace Drupal\devshop_task\Plugin\DevshopTaskType;

use Drupal\devshop_task\DevshopTaskTypePluginBase;

/**
 * Plugin implementation of the devshop_task_type.
 *
 * @DevshopTaskType(
 *   id = "foo",
 *   label = @Translation("Foo"),
 *   description = @Translation("Foo description."),
 *   command = "echo 'bar'"
 * )
 */
class Foo extends DevshopTaskTypePluginBase {

}
