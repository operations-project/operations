<?php

namespace Drupal\site;

use Drupal\site\Entity\SiteDefinition;

/**
 * A trait for sharing Site Entity metadata across entities.
 */
trait SiteEntityTrait {

  /**
   * The title of the site.
   *
   * @var string
   */
  protected string $site_title;

  /**
   * The UUID of the site.
   *
   * @var string
   */
  protected string $site_uuid;

  /**
   * The URI of the site.
   *
   * @var string
   */
  protected string $site_uri;

  /**
   * The primary production URI of the site.
   *
   * @var string
   */
  protected string $canonical_uri;

  /**
   * A list of fields to set from remote site entity.
   *
   * These fields are not sent
   *
   * @var array
   */
  protected array $fields_allow_override = [];

  /**
   * A list of config objects to load into the Entity.
   *
   * @var array
   */
  protected array $configs_load = [];

  /**
   * A list of config objects to allow setting from remote Site Manager.
   *
   * The Site Connect module sends this entity to Site API, which can return
   * config data. If the configs are in $configs_allow_override, they will be set
   * on the client site.
   *
   * @var array
   */
  protected array $configs_allow_override = [];

  /**
   * A list of state objects to load into the Entity.
   *
   * @var array
   */
  protected array $states_load = [];

  /**
   * A list of states to allow setting from remote Site Manager.
   *
   * @var array
   */
  protected array $states_allow_override = [];

  /**
   * An arbitrary array of data.
   *
   * @var array
   */
  protected array $data;

  /**
   * The site state: SITE_OK, SITE_WARN, SITE_ERROR
   *
   * @var int
   */
  protected int $state = SiteDefinition::SITE_OK;

  /**
   * The factors that can affect this site's state.
   *
   * @var array
   */
  protected array $state_factors;

  /**
   * A string to describe the reason a site is in a certain state.
   *
   * @var string
   */
  protected string $reason = '';

  /**
   * A description of the site.
   *
   * @var string
   */
  protected string $description;

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
    return self::getStateName($this->state);
  }
}