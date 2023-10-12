<?php

namespace Drupal\site\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Project type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "project_type",
 *   label = @Translation("Project type"),
 *   label_collection = @Translation("Project types"),
 *   label_singular = @Translation("project type"),
 *   label_plural = @Translation("projects types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count projects type",
 *     plural = "@count projects types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\site\Form\ProjectTypeForm",
 *       "edit" = "Drupal\site\Form\ProjectTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\site\ProjectTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer project types",
 *   bundle_of = "project",
 *   config_prefix = "project_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/project_types/add",
 *     "edit-form" = "/admin/structure/project_types/manage/{project_type}",
 *     "delete-form" = "/admin/structure/project_types/manage/{project_type}/delete",
 *     "collection" = "/admin/structure/project_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class ProjectType extends ConfigEntityBundleBase {

  /**
   * The machine name of this project type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the project type.
   *
   * @var string
   */
  protected $label;

}
