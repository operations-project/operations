<?php

namespace Drupal\site;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a site environment entity type.
 */
interface SiteEnvironmentInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
