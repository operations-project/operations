<?php

namespace Drupal\devshop_task;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a task entity type.
 */
interface TaskInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
