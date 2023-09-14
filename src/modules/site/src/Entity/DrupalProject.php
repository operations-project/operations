<?php

namespace Drupal\site\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\site\DrupalProjectInterface;
use Drupal\site\SitePropertyBundleFieldDefinitionsTrait;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the drupal project entity class.
 *
 * @ContentEntityType(
 *   id = "drupal_project",
 *   label = @Translation("Drupal Project"),
 *   label_collection = @Translation("Drupal Projects"),
 *   label_singular = @Translation("drupal project"),
 *   label_plural = @Translation("drupal projects"),
 *   label_count = @PluralTranslation(
 *     singular = "@count drupal projects",
 *     plural = "@count drupal projects",
 *   ),
 *   bundle_label = @Translation("Drupal Project type"),
 *   handlers = {
 *     "list_builder" = "Drupal\site\DrupalProjectListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\site\DrupalProjectAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\site\Form\DrupalProjectForm",
 *       "edit" = "Drupal\site\Form\DrupalProjectForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "operations_site_drupal_project",
 *   data_table = "operations_site_drupal_project_fields",
 *   revision_table = "operations_site_drupal_project_revision",
 *   revision_data_table = "operations_site_drupal_project_fields",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer drupal project types",
 *   entity_keys = {
 *     "id" = "drupal_site_uuid",
 *     "owner" = "uid",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "langcode" = "langcode",
 *     "bundle" = "drupal_project_type",
 *     "label" = "drupal_site_name",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "collection" = "/admin/content/drupal-project",
 *     "add-form" = "/drupal/add/{drupal_project_type}",
 *     "add-page" = "/drupal/add",
 *     "canonical" = "/drupal/{drupal_project}",
 *     "edit-form" = "/drupal/{drupal_project}/edit",
 *     "delete-form" = "/drupal/{drupal_project}/delete",
 *   },
 *   bundle_entity_type = "drupal_project_type",
 *   field_ui_base_route = "entity.drupal_project_type.edit_form",
 * )
 */
class DrupalProject extends RevisionableContentEntityBase implements DrupalProjectInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use RedirectDestinationTrait;
  use SitePropertyBundleFieldDefinitionsTrait;

  /**
   * Alter fields like git_remote to make them editable.
   */
  public static function propertyFieldDefinitionsAlter(&$fields, $bundle, $base_field_definitions)
  {
    if (!empty($fields['git_remote'])) {
      $fields['git_remote']
        ->setDisplayOptions('form', [
          'type' => 'string_textfield',
        ])
        ->setDisplayConfigurable('form', TRUE)
      ;
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * Load the Drupal site entity for this site.
   * @return DrupalProject
   */
  public static function loadSelf() {
    return static::load(DrupalProject::getSiteUuid());
  }

  /**
   * Load the site entity with the same UUID as this site.
   */
  public function isSelf() {
    return DrupalProject::getSiteUuid() == $this->drupal_site_uuid->value;
  }

  /**
   * @return string
   *   The site's uuid.
   */
  public static function getSiteUuid() {
    return \Drupal::config('system.site')->get('uuid');
  }

  /**
   * @return string
   *   The site's name.
   */
  public static function getSiteName() {
    return \Drupal::config('system.site')->get('name');
  }

  /**
   * Returns the view array.
   *
   * @return array
   */
  public function view($mode = 'full') {
    return  $this->entityTypeManager()->getViewBuilder($this->getEntityTypeId())->view($this, $mode);;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    $entity = $this;

    if (!$entity->id()) {
      return [];
    }
    $operations = $this->getDefaultOperations($entity);
    $operations += \Drupal::moduleHandler()->invokeAll('entity_operation', [$entity]);
    \Drupal::moduleHandler()->alter('entity_operation', $operations, $entity);
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $operations;
  }

  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected function getDefaultOperations() {
    $entity = $this;
    $operations = [];
    if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
      $operations['edit'] = [
        'title' => t('Edit Site Information'),
        'weight' => 10,
        'url' => $this->ensureDestination($entity->toUrl('edit-form')),
      ];
    }
    if (!$entity->isSelf() && $entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
      $operations['delete'] = [
        'title' => t('Delete'),
        'weight' => 100,
        'url' => $this->ensureDestination($entity->toUrl('delete-form')),
      ];
    }

    return $operations;
  }

  /**
   * Ensures that a destination is present on the given URL.
   *
   * @param \Drupal\Core\Url $url
   *   The URL object to which the destination should be added.
   *
   * @return \Drupal\Core\Url
   *   The updated URL object.
   */
  protected function ensureDestination(Url $url) {
    return $url->mergeOptions(['query' => $this->getRedirectDestination()->getAsArray()]);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['drupal_site_uuid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Site UUID'))
      ->setDescription(t('The Drupal site UUID.'))
      ->setRequired(true)
      ->setReadOnly(true)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['drupal_site_name'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Drupal Site Name'))
      ->setDescription(t('Enter the name of this Drupal Site.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
    ;

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Creator'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Added on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the drupal site was added to this site.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the drupal site was last edited.'))
      ->setDisplayConfigurable('view', TRUE);
    ;

    $fields['canonical_url'] = BaseFieldDefinition::create('uri')
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setLabel(t('Canonical URL'))
      ->setDescription(t('The primary live URL for this site.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'link',
      ])
    ;

    return $fields;
  }

}
