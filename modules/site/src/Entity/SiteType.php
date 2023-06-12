<?php

namespace Drupal\site\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the site type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "site_type",
 *   label = @Translation("Site type"),
 *   label_collection = @Translation("Site types"),
 *   label_singular = @Translation("Site type"),
 *   label_plural = @Translation("Site types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count site type",
 *     plural = "@count site types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\site\Form\SiteTypeForm",
 *       "edit" = "Drupal\site\Form\SiteTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\site\SiteTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer site types",
 *   bundle_of = "site",
 *   config_prefix = "site_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/operations/site_types/add",
 *     "edit-form" = "/admin/operations/site_types/manage/{site_type}",
 *     "delete-form" = "/admin/operations/site_types/manage/{site_type}/delete",
 *     "collection" = "/admin/operations/site_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class SiteType extends ConfigEntityBundleBase {

  /**
   * The machine name of this site type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the site type.
   *
   * @var string
   */
  protected $label;

}
