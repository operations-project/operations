<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\SitePropertyPluginBase;

/**
 * Plugin implementation of the site_property.
 *
 * @SiteProperty(
 *   id = "php_version",
 *   name = "php_version",
 *   label = @Translation("PHP Version"),
 *   description = @Translation("The version of PHP the site is running.")
 * )
 */
class PhpVersion extends SitePropertyPluginBase {

  public function value() {
    return phpversion();
  }
  /**
   * Define a
   *
   * @return static
   *   A new field definition object.
   */
  public function baseFieldDefinitions(EntityTypeInterface $entity_type, &$fields) {
    $fields[$this->name()] = BaseFieldDefinition::create('string')
      ->setLabel($this->label())
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);
  }
}
