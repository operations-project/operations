<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\SitePropertyPluginBase;

/**
 * Plugin implementation of the site_property.
 *
 * @SiteProperty(
 *   id = "host_provider",
 *   name = "host_provider",
 *   default = "unknown",
 *   label = @Translation("Host Provider"),
 *   description = @Translation("A string with information about the host provider."),
 *   site_bundles = {
 *     "Drupal\site\Entity\Bundle\DefaultSiteBundle"
 *   },
 * )
 */
class HostProvider extends SitePropertyPluginBase {

  /**
   * Load git remote .git directory, if it exists.
   * @return array|false|mixed|string|string[]|void
   */
  public function value() {
    return getenv('DRUPAL_SITE_HOST_PROVIDER') ?? '';
  }

  /**
   * Define the Git Reference field.
   *
   * @return static
   *   A new field definition object.
   */
  static public function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields['host_provider'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Host Provider'))
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
