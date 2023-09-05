<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\site\Entity\SiteDefinition;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\SitePropertyPluginBase;

/**
 * Plugin implementation of the site_property.
 *
 * @SiteProperty(
 *   id = "drupal_site_uuid",
 *   name = "drupal_site_uuid",
 *   site_bundles = {
 *     "Drupal\site\Entity\Bundle\DrupalSiteBundle",
 *     "Drupal\site_manager\Entity\SiteGroup\DrupalSiteGroup",
 *   },
 *   label = @Translation("Drupal Site UUID"),
 *   description = @Translation("The unique identifyer of this Drupal site.")
 * )
 */
class DrupalSiteUuid extends SitePropertyPluginBase {

  public function value() {
    return \Drupal::config('system.site')->get('uuid');
  }

  /**
   * {@inheritdoc }
   */
  static public function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields['drupal_site_uuid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Drupal Site UUID'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
    ;
    $fields['drupal_project'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Drupal Project'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'drupal_project')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -90,
      ])
    ;

    return $fields;
  }

}
