<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\SitePropertyPluginBase;

/**
 * Plugin implementation of the site_property.
 *
 * @SiteProperty(
 *   id = "drupal_env",
 *   name = "drupal_env",
 *   default = "",
 *   label = @Translation("Drupal Environment"),
 *   description = @Translation("A simple string representing this environment. For example: prod or dev."),
 *   site_bundles = {
 *     "Drupal\site\Entity\Bundle\DrupalSiteBundle"
 *   },
 * )
 */
class DrupalEnv extends SitePropertyPluginBase {

  /**
   * @inheritdoc
   */
  public function value() {
    return getenv('DRUPAL_ENV') ?? '';
  }

  /**
   * @inheritdoc
   */
  static public function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields['drupal_env'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Drupal Environment'))
      ->setDescription(t('The value of the DRUPAL_ENV environment variable.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
    ;
    return $fields;
  }
}
