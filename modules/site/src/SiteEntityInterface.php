<?php

namespace Drupal\site;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a site entity type.
 */
interface SiteEntityInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
