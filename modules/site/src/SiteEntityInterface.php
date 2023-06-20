<?php

namespace Drupal\site;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides a standard interface that can be used with config and content entities.
 */
interface SiteEntityInterface extends SiteInterface, ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
