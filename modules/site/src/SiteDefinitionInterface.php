<?php

namespace Drupal\site;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides a standard interface that can be used with config and content entities.
 */
interface SiteDefinitionInterface extends SiteInterface, ConfigEntityInterface {

}
