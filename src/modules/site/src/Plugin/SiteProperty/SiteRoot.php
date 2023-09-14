<?php

namespace Drupal\site\Plugin\SiteProperty;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\SitePropertyPluginBase;

/**
 * Plugin implementation of the site_property.
 *
 * @SiteProperty(
 *   id = "site_root",
 *   name = "site_root",
 *   default = "",
 *   label = @Translation("Site Root"),
 *   description = @Translation("Path to the root directory of this site."),
 *   site_bundles = {
 *     "Drupal\site\Entity\Bundle\DefaultSiteBundle"
 *   },
 * )
 */
class SiteRoot extends SitePropertyPluginBase {

  /**
   * Returns the directory above "/vendor".
   * @inheritdoc
   */
  public function value() {
    return SiteEntity::getSiteRoot();
  }

  /**
   * @inheritdoc
   */
  static public function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields['site_root'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Root path'))
      ->setDescription(t('The root path of this site.'))
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
