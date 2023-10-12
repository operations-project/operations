<?php

namespace Drupal\site\Entity\ProjectBundle;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\site\JsonApiEntityTrait;
use Drupal\user\EntityOwnerTrait;

/**
 * A bundle class for project entities.
 */
class DrupalProjectBundle extends CodeProject {


  use EntityChangedTrait;
  use EntityOwnerTrait;
  use RedirectDestinationTrait;
  use JsonApiEntityTrait;

  /**
   * @inheritDoc
   */
  static public function propertyFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions)
  {
    $fields = parent::propertyFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    $fields['drupal_site_uuid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Site UUID'))
      ->setDescription(t('The Drupal site UUID.'))
      ->setRequired(false)
      ->setReadOnly(true)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Load the Drupal project entity for this site.
   * @return DrupalProjectBundle
   */
  public static function loadSelf() {
    return static::loadByDrupalSiteUuid(DrupalProjectBundle::getSiteUuid());
  }

  /**
   * @param $uuid string Site UUID.
   */
  static public function loadByDrupalSiteUuid($uuid) {

    if (empty($uuid)) {
      return;
    }
    $projects = \Drupal::entityTypeManager()
      ->getStorage('project')
      ->loadByProperties([
        'drupal_site_uuid' => $uuid,
      ]);
    $project = array_shift($projects);
    if ($project) {
      return static::load($project->id());
    }
  }

  /**
   * Load the site entity with the same UUID as this site.
   */
  public function isSelf() {
    return $this->getSiteUuid() == $this->drupal_site_uuid->value;
  }

  /**
   * @return string
   *   The site's uuid.
   */
  public static function getSiteUuid() {
    return \Drupal::config('system.site')->get('uuid');
  }

  /**
   * @return string
   *   The site's name.
   */
  public static function getSiteName() {
    return \Drupal::config('system.site')->get('name');
  }
}
