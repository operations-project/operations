<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\SitePropertyPluginBase;

/**
 * The API url to use when posting back to the site.
 *
 * @SiteProperty(
 *   id = "api_url",
 *   name = "api_url",
 *   site_bundles = {
 *     "Drupal\site\Entity\Bundle\DrupalSiteBundle"
 *   },
 *   hidden = true,
 *   label = @Translation("API Url"),
 *   description = @Translation("The API url to use when posting back to this site.")
 * )
 *
 * @TODO: Allow Property plugins to opt out of being included as a SiteDefinition property.
 */
class ApiUrl extends SitePropertyPluginBase {

  /**
   * {@inheritdoc }
   */
  static public function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields['api_url'] = BaseFieldDefinition::create('uri')
      ->setRevisionable(TRUE)
      ->setLabel(t('Site API URI'))
      ->setDescription(t('The URL of the API endpoint for this site. If left empty, the Site URI will be used.'))
      ->setRequired(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'uri',
      ])
      ->setDisplayConfigurable('view', TRUE);
    ;
    return $fields;
  }
}
