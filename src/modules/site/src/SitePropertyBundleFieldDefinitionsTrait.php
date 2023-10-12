<?php

namespace Drupal\site;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Add to Entity classes to enable SiteProperty Plugins to be attached via
 * "site_bundles" value.
 */
trait SitePropertyBundleFieldDefinitionsTrait {


//  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions)
//  {
//    $fields = self::propertyFieldDefinitions($entity_type, $bundle, $base_field_definitions);
//    return $fields;
//  }

  /**
   * Load all field definitions for all bundles of this entity type.
   * alled by site_entity_field_storage_info
   * @param EntityTypeInterface $entity_type
   * @param $bundle
   * @param array $base_field_definitions
   * @return void
   */
  static public function allPropertyFieldDefinitions(EntityTypeInterface $entity_type, array $base_field_definitions = []) {
    $EntityTypeBundleInfo = \Drupal::service('entity_type.bundle.info');
    $EntityTypeBundleInfo->clearCachedBundles();

    // During install, bundles are NOT AVAILABLE, so this magic function does not
    // work on module install.
    // As a workaround, site.install reinstalls the field definitions.
    // @TODO: See site_install()
    $bundles = $EntityTypeBundleInfo->getBundleInfo($entity_type->id());
    $fields = [];
    foreach ($bundles as $bundle => $bundle_info) {
      $fields += $bundle_info['class']::propertyFieldDefinitions($entity_type, $bundle, $base_field_definitions);
    }
    return $fields;
  }

  /**
   * Load all SiteProperty fields for this bundle.
   *
   * Analogous to bundleFieldDefinitions()
   *
   * Loaded by site_entity_bundle_field_info().
   *
   * @see site_entity_bundle_field_info()
   * @inheritdoc
   */
  static public function propertyFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions)
  {
    $fields = [];

    // Get this bundle's bundle info.
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type->id());
    if (empty($bundles[$bundle])) {
      return $fields;
    }

    $this_bundle_class = $bundles[$bundle]['class'] ?? $entity_type->getClass();

    // Load all plugins.
    $type = \Drupal::service('plugin.manager.site_property');
    $plugin_definitions = $type->getDefinitions();


    foreach ($plugin_definitions as $name => $plugin_definition) {
      $plugin = $type->createInstance($plugin_definition['id']);
      if (!empty($plugin_definition['site_bundles'])) {
        foreach ($plugin_definition['site_bundles'] as $plugin_site_bundle_class) {
          if ($this_bundle_class == $plugin_site_bundle_class || is_subclass_of($this_bundle_class, $plugin_site_bundle_class)) {
            if (method_exists(get_class($plugin), 'bundleFieldDefinitions')) {
              $bundle_fields = $plugin->bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);
              // Set necessary bundle fields
              // @see http://www.noreiko.com/blog/defining-bundle-fields-code
              foreach ($bundle_fields as $name => &$field) {
                $field->setName($name);
                $field->setTargetEntityTypeId($entity_type->id());
                $field->setTargetBundle($bundle);
              }
              $fields += $bundle_fields;
            }
          }
        }
      }
    }

    if (method_exists(static::class, 'propertyFieldDefinitionsAlter')) {
      static::propertyFieldDefinitionsAlter($fields, $bundle, $base_field_definitions);
    }
    return $fields;
  }
}

