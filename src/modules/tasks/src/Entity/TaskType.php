<?php

namespace Drupal\devshop_task\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Task type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "task_type",
 *   label = @Translation("Task type"),
 *   label_collection = @Translation("Task types"),
 *   label_singular = @Translation("task type"),
 *   label_plural = @Translation("tasks types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count tasks type",
 *     plural = "@count tasks types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\devshop_task\Form\TaskTypeForm",
 *       "edit" = "Drupal\devshop_task\Form\TaskTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\devshop_task\TaskTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer task types",
 *   bundle_of = "task",
 *   config_prefix = "task_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/task_types/add",
 *     "edit-form" = "/admin/structure/task_types/manage/{task_type}",
 *     "delete-form" = "/admin/structure/task_types/manage/{task_type}/delete",
 *     "collection" = "/admin/structure/task_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "plugin",
 *   }
 * )
 */
class TaskType extends ConfigEntityBundleBase {

  /**
   * The machine name of this task type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the task type.
   *
   * @var string
   */
  protected $label;

  /**
   * The plugin to run for tasks of this type.
   *
   * @var string
   */
  protected $plugin = '';

  public function plugin() {
    return $this->plugin;
  }
}
