<?php

namespace Drupal\site;

use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\Normalizer\ResourceObjectNormalizer;

/**
 * Helpers for JSON API.
 */
trait JsonApiEntityTrait {

  /**
   * Generate a JSON:API object.
   *
   * @return array
   */
  public function toJsonApiArray() {
    # @see EntityResource
    $resource_object = $this->toResourceObject();
    $serializer = \Drupal::service('jsonapi.serializer');
    $cacher =  \Drupal::service('jsonapi.normalization_cacher');
    $normalizer = new ResourceObjectNormalizer($cacher);
    $normalizer->setSerializer($serializer);
    $data = $normalizer->normalize($resource_object, 'api_json', [
      'account' => \Drupal::currentUser()->getAccount(),
    ]);

    $data = $data->getNormalization();
    // If site entity has a drupalProject, include it.
    if (method_exists($this, 'drupalProject') && $this->drupalProject()) {
      $data['included'][] = $this->drupalProject()->toJsonApiArray();
    }
    return $data;
  }

  public function toResourceObject() {
    $resource_type = \Drupal::service('jsonapi.resource_type.repository')->get($this->getEntityTypeId(), $this->bundle());
    return ResourceObject::createFromEntity($resource_type, $this);
  }

  public function updateFromJsonApiData(array $data) {
    foreach ($data as $name => $value) {
      if ($this->hasField($name)) {

        switch ($this->getFieldDefinition($name)->getType()) {
          case 'timestamp':
          case 'created':
          case 'changed':
            $value = $value ? strtotime($value) : '';
            break;

          case 'date':
            $value = $value ? date('Y-m-d', strtotime($value)) : '';
              break;

          case 'datetime':
            $value = $value ? date('Y-m-d\TH:i:s', strtotime($value)) : '';
              break;
        }
        $this->set($name, $value);
      }
    }
  }
}
