<?php

namespace Drupal\site_manager\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\SitePropertyPluginBase;

/**
 * The API key to use when posting back to the site.
 *
 * @SiteProperty(
 *   id = "api_key",
 *   name = "api_key",
 *   label = @Translation("API Key"),
 *   description = @Translation("The API key to use when posting back to this site.")
 * )
 */
class ApiKey extends SitePropertyPluginBase {

  public function value() {
    return 'abcdefg';
  }

  /**
   * Define a
   *
   * @return static
   *   A new field definition object.
   */
  public function baseFieldDefinitions(EntityTypeInterface $entity_type, &$fields) {
    $fields['api_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('API Key'))
      ->setDescription(t('An API Key from the site. If entered, site data from here will post back to the client site when saving this form.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_field',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'basic_string',
        'label' => 'above',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
    ;
  }
}
