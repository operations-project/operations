<?php

namespace Drupal\site;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a drupal project entity type.
 */
interface DrupalProjectInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
