<?php

namespace Drupal\site\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\site\SiteDefinitionInterface;

/**
 * Defines the site definition entity type.
 *
 * @ConfigEntityType(
 *   id = "site_definition",
 *   label = @Translation("Site Definition"),
 *   label_collection = @Translation("Site Definitions"),
 *   label_singular = @Translation("site definition"),
 *   label_plural = @Translation("site definitions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count site definition",
 *     plural = "@count site definitions",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\site\SiteDefinitionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\site\Form\SiteDefinitionForm",
 *       "edit" = "Drupal\site\Form\SiteDefinitionForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "site_definition",
 *   admin_permission = "administer site_definition",
 *   links = {
 *    "collection" = "/admin/operations/sites/definitions",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description"
 *   }
 * )
 */
class SiteDefinition extends ConfigEntityBase implements SiteDefinitionInterface {

  /**
   * The site definition ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The site definition label.
   *
   * @var string
   */
  protected $label;

  /**
   * The site definition status.
   *
   * @var bool
   */
  protected $status;

  /**
   * The site_definition description.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {

    // Load detectable properties if self.
    if ($values['id'] == 'self') {

      if (empty($values['label'])) {
        $values['label'] = \Drupal::config('system.site')->get('name');
      }

    }

    parent::__construct($values, $entity_type);
  }
}
