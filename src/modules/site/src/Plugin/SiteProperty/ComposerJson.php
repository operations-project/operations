<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\SitePropertyPluginBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Gather composer.json. Stored in "data" field.
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
      $value = Yaml::parseFile(SiteEntity::getSiteRoot() . '/composer.json');
      $this->addData('contents', $value);
      return $value;
    }
    catch (\Exception $e) {
      return [];
    }
  }
}
