<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\SitePropertyPluginBase;

/**
 * Plugin implementation of the site_property.
 *
 * @SiteProperty(
 *   id = "drupal_install_time",
 *   name = "drupal_install_time",
 *   site_bundles = {
 *     "Drupal\site\Entity\Bundle\DrupalSiteBundle"
 *   },
 *   label = @Translation("Install time"),
 *   description = @Translation("The date the site was originally installed.")
 * )
 */
class DrupalInstallTime extends SitePropertyPluginBase {

  public function value() {
    return \Drupal::state()->get('install_time');
  }

  /**
   * Define a
   *
   * @return static
   *   A new field definition object.
   */
  static public function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields['drupal_install_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Installed'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp_ago',
        'settings' =>  [
          'future_format' => '@interval from now',
          'past_format' => '@interval ago',
          'granularity' => 1,
        ],
      ])
    ;
    return $fields;
  }
}
