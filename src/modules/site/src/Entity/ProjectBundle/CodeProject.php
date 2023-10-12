<?php

namespace Drupal\site\Entity\ProjectBundle;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * A bundle class for project entities.
 */
class CodeProject extends WebsiteProject {

  /**
   * @inheritDoc
   */
  static public function propertyFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions)
  {
    $fields = parent::propertyFieldDefinitions($entity_type, $bundle, $base_field_definitions);
    $fields['git_remote'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Git Remote'))
      ->setDescription(t('The clone URL for the git repository that stores this sites code.'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
    ;
    return $fields;
  }
}
