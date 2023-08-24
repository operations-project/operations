<?php

namespace Drupal\site;

use Drupal\site\Entity\SiteDefinition;

/**
 * A trait for sharing Site Entity metadata across entities.
 */
trait SiteEntityTrait {

  /**
   * @param $state
   * @return string
   */
  static public function getStateName($state): string
  {
    return self::STATE_NAMES[$state] ?? '';
  }

  /**
   * @param $state
   * @return string
   */
  static public function getStateClass($state): string
  {
    return self::STATE_CLASSES[$state] ?? '';
  }

  /**
   * Return the current state name.
   * @return string
   */
  public function stateName(): string
  {
    return self::getStateName($this->state->value);
  }

  /**
   * Return the current state name.
   * @return string
   */
  public function stateClass(): string
  {
    return self::getStateClass($this->state->value);
  }
}