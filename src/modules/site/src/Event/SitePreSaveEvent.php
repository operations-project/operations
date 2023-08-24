<?php

namespace Drupal\site\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\site\Entity\SiteEntity;

class SitePreSaveEvent extends Event {
  const SITE_PRESAVE = 'site_entity_pre_save';

  /**
   * The Site entity about to be saved.
   * @var SiteEntity
   */
  public $site_entity;

  /**
   * @param SiteEntity $site_entity
   */
  public function __construct(SiteEntity $site_entity) {
    $this->site_entity = $site_entity;
  }


}
