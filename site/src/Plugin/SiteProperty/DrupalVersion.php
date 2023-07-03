<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\SitePropertyPluginBase;

/**
 * Plugin implementation of the site_property.
 *
 * @SiteProperty(
 *   id = "drupal_version",
 *   name = "drupal_version",
 *   label = @Translation("Drupal Version"),
 *   description = @Translation("The version of Drupal the site is running.")
 * )
 */
class DrupalVersion extends SitePropertyPluginBase {

  public function value() {
    return \Drupal::VERSION;
  }

  /**
   * Define a
   *
   * @return static
   *   A new field definition object.
   */
  public function baseFieldDefinitions(EntityTypeInterface $entity_type, &$fields) {
    $fields['drupal_version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Drupal Version'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);
  }
}
