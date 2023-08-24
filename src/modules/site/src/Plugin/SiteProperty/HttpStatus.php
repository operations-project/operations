<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Url;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\SiteInterface;
use Drupal\site\SitePropertyPluginBase;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

/**
 * Plugin implementation of the site_property.
 *
 * @SiteProperty(
 *   id = "http_status",
 *   name = "http_status",
 *   label = @Translation("HTTP Status"),
 *   description = @Translation("The response code coming from the site."),
 *   remote = "true",
 *   context_definitions = {
 *     "site" = @ContextDefinition("entity:site", label = @Translation("Site"), required = false)
 *   },
 *   site_bundles = {
 *     "Drupal\site\Entity\Bundle\SiteBundle"
 *   },
 * )
 */
class HttpStatus extends SitePropertyPluginBase {


  /**
   * {@inheritdoc}
   */
  public function state() {

    if ($this->value == 200) {
      return SiteInterface::SITE_OK;
    }
    elseif ($this->value >= 500) {
      return SiteInterface::SITE_ERROR;
    }
    else {
      return SiteInterface::SITE_WARN;
    }

  }

  /**
   * Define a
   *
   * @return static
   *   A new field definition object.
   */
  static public function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields['http_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('HTTP Status Code'))
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
