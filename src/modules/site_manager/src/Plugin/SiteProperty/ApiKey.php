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
 *   hidden = true,
 *   label = @Translation("API Key"),
 *   description = @Translation("The API key to use when posting back to this site.")
 * )
 *
 * @TODO: Allow Property plugins to opt out of being included as a SiteDefinition property.
 */
class ApiKey extends SitePropertyPluginBase {

  /**
   * @return string
   */
  public function value() {
    return '';
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
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
    ;
  }
}
