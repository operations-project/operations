<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\SitePropertyPluginBase;

/**
 * The API key to use when posting back to the site.
 *
 * @SiteProperty(
 *   id = "api_key",
 *   name = "api_key",
 *   site_bundles = {
 *     "Drupal\site\Entity\Bundle\DrupalSiteBundle",
 *     "Drupal\site\Entity\DrupalProject"
 *   },
 *   hidden = true,
 *   label = @Translation("API Key"),
 *   description = @Translation("The API key to use when posting back to this site.")
 * )
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
  static public function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields['api_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('API Key'))
      ->setDescription(t('An API Key from the site. If entered, site data from here will post back to the client site when saving this form.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
    ;
    return $fields;
  }
}
