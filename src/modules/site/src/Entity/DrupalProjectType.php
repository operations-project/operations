<?php

namespace Drupal\site\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Drupal Project type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "drupal_project_type",
 *   label = @Translation("Drupal Project type"),
 *   label_collection = @Translation("Drupal Project types"),
 *   label_singular = @Translation("drupal project type"),
 *   label_plural = @Translation("drupal projects types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count drupal projects type",
 *     plural = "@count drupal projects types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\site\Form\DrupalProjectTypeForm",
 *       "edit" = "Drupal\site\Form\DrupalProjectTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\site\DrupalProjectTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer drupal project types",
 *   bundle_of = "drupal_project",
 *   config_prefix = "drupal_project_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/drupal_project_types/add",
 *     "edit-form" = "/admin/structure/drupal_project_types/manage/{drupal_project_type}",
 *     "delete-form" = "/admin/structure/drupal_project_types/manage/{drupal_project_type}/delete",
 *     "collection" = "/admin/structure/drupal_project_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class DrupalProjectType extends ConfigEntityBundleBase {

  /**
   * The machine name of this drupal project type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the drupal project type.
   *
   * @var string
   */
  protected $label;

}
