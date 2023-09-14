<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\SitePropertyPluginBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Gather composer.json.
 *
 * @SiteProperty(
 *   id = "composer_json",
 *   name = "composer_json",
 *   label = @Translation("Composer.json Content"),
 *   description = @Translation("The contents of composer.json."),
 *   site_bundles = {
 *     "Drupal\site\Entity\Bundle\PhpSiteBundle"
 *   },
 * )
 */
class ComposerJson extends SitePropertyPluginBase {

  /**
   * @inheritdoc
   */
  public function value() {
    try {
      return Yaml::parseFile(SiteEntity::getSiteRoot() . '/composer.json');
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * @inheritdoc
   */
  static public function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields['composer_json'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Composer.json'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      // @TODO: Create a module for viewing map fields.
//      ->setDisplayOptions('view', [
//        'label' => 'above',
//        'type' => 'yaml',
//      ])
    ;
    return $fields;
  }
}
