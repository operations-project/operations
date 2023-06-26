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
 *   label = @Translation("Host Provider"),
 *   description = @Translation("A string with information about the host provider.")
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
  public function baseFieldDefinitions(EntityTypeInterface $entity_type, &$fields) {
    $fields['host_provider'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Host Provider'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);
  }
}
