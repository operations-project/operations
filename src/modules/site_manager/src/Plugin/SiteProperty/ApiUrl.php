<?php

namespace Drupal\site_manager\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\SitePropertyPluginBase;

/**
 * The API url to use when posting back to the site.
 *
 * @SiteProperty(
 *   id = "api_url",
 *   name = "api_url",
 *   hidden = true,
 *   label = @Translation("API Url"),
 *   description = @Translation("The API url to use when posting back to this site.")
 * )
 *
 * @TODO: Allow Property plugins to opt out of being included as a SiteDefinition property.
 */
class ApiUrl extends SitePropertyPluginBase {

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
    $fields['api_uri'] = BaseFieldDefinition::create('uri')
      ->setRevisionable(TRUE)
      ->setLabel(t('Site API URI'))
      ->setDescription(t('The URL of the API endpoint for this site. If left empty, the Site URI will be used.'))
      ->setRequired(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'uri',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'uri_link',
      ])
      ->setDisplayConfigurable('view', TRUE);
    ;
  }
}
