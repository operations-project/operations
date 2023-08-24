<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\SitePropertyPluginBase;

/**
 * Plugin implementation of the site_property.
 *
 * @SiteProperty(
 *   id = "drupal_install_profile",
 *   name = "drupal_install_profile",
 *   site_bundles = {
 *     "Drupal\site\Entity\Bundle\DrupalSiteBundle"
 *   },
 *   label = @Translation("Install profile"),
 *   description = @Translation("The install profile used to install the site.")
 * )
 */
class DrupalInstallProfile extends SitePropertyPluginBase {

  public function value() {
    return \Drupal::service('config.factory')->get('core.extension')->get('profile');

  }

  /**
   * Define a
   *
   * @return static
   *   A new field definition object.
   */
  static public function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields['drupal_install_profile'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Install profile'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
      ])
    ;
    return $fields;
  }
}
