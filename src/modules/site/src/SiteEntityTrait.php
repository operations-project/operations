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
   * @param $state
   * @return string
   */
  static public function getStateValue($id): string
  {
    $values = array_flip(self::STATE_IDS);
    if (!isset($values[$id])) {
      throw new \Exception(dt('Invalid state. Allowed values are: ok, warning, error, info, processing'));
    }
    return $values[$id] ?? '';
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

  /**
   * Return the integer value of a state from it's ID..
   * @return string
   */
  public function stateValue(): string
  {
    $values = array_flip(self::STATE_IDS);
    return $values[$this->state->value] ?? '';
  }

  /**
   * Return the short string value of a state.
   * @return string
   */
  public function stateId(): string
  {
    return self::STATE_IDS[$this->state->value] ?? '';
  }

  /**
   * Return the integer value of a state from it's ID..
   * @return string
   */
  public function stateIcon(): string
  {
    $values = self::STATE_ICONS;
    return $values[$this->state->value] ?? '';
  }
}
