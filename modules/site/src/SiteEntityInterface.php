<?php

namespace Drupal\site;

use Consolidation\SiteAlias\SiteAliasInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a site entity type.
 */
interface SiteEntityInterface extends SiteInterface, ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
