<?php

namespace Drupal\site;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a project entity type.
 */
interface ProjectInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
