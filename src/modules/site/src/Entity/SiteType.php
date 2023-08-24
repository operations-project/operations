<?php

namespace Drupal\site\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Site type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "site_type",
 *   label = @Translation("Site type"),
 *   label_collection = @Translation("Site types"),
 *   label_singular = @Translation("site type"),
 *   label_plural = @Translation("sites types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count sites type",
 *     plural = "@count sites types",
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
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/site_types/add",
 *     "edit-form" = "/admin/structure/site_types/manage/{site_type}",
 *     "delete-form" = "/admin/structure/site_types/manage/{site_type}/delete",
 *     "collection" = "/admin/structure/site_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "label_collection",
 *     "label_singular",
 *     "label_plural",
 *     "label_count_singular",
 *     "label_count_plural",
 *     "description",
 *     "help",
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
  protected $label_collection = 'Sites';
  protected $label_singular = 'site';
  protected $label_plural = 'sites';
  protected $label_count_singular = '@count site';
  protected $label_count_plural = '@count sites';

  /**
   * A brief description of this node type.
   *
   * @var string
   */
  protected $description;

  /**
   * Help information shown to the user when creating a Node of this type.
   *
   * @var string
   */
  protected $help;

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    return $this->help;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }
}
