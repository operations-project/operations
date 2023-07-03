<?php

namespace Drupal\site;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides a standard interface that can be used with config and content entities.
 */
interface SiteEntityInterface extends RevisionableInterface, SiteInterface, ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
