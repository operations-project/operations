<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\SitePropertyPluginBase;

/**
 * Plugin implementation of the site_property.
 *
 * @SiteProperty(
 *   id = "drupal_cron_last",
 *   name = "drupal_cron_last",
 *   site_bundles = {
 *     "Drupal\site\Entity\Bundle\DrupalSiteBundle"
 *   },
 *   label = @Translation("Last cron run"),
 *   description = @Translation("The last time cron was run on the site.")
 * )
 */
class DrupalCronLast extends SitePropertyPluginBase {

  public function value() {
    return \Drupal::state()->get('system.cron_last');
  }

  /**
   * Define a
   *
   * @return static
   *   A new field definition object.
   */
  static public function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields['drupal_cron_last'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last cron run'))
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
