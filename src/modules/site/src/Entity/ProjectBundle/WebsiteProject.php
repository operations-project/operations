<?php

namespace Drupal\site\Entity\ProjectBundle;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * A bundle class for project entities.
 */
class WebsiteProject extends ProjectBundle {

  /**
   * @inheritDoc
   */
  static public function propertyFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions)
  {
    $fields = parent::propertyFieldDefinitions($entity_type, $bundle, $base_field_definitions);
    $fields['canonical_url'] = BaseFieldDefinition::create('uri')
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setLabel(t('Canonical URL'))
      ->setDescription(t('The primary live URL for this site.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'link',
      ])
    ;
    return $fields;
  }
}
