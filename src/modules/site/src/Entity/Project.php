<?php

namespace Drupal\site\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\site\ProjectInterface;
use Drupal\site\SitePropertyBundleFieldDefinitionsTrait;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the project entity class.
 *
 * @ContentEntityType(
 *   id = "project",
 *   label = @Translation("Project"),
 *   label_collection = @Translation("Projects"),
 *   label_singular = @Translation("project"),
 *   label_plural = @Translation("projects"),
 *   label_count = @PluralTranslation(
 *     singular = "@count projects",
 *     plural = "@count projects",
 *   ),
 *   bundle_label = @Translation("Project type"),
 *   handlers = {
 *     "list_builder" = "Drupal\site\ProjectListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\site\ProjectAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\site\Form\ProjectForm",
 *       "edit" = "Drupal\site\Form\ProjectForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "operations_project",
 *   data_table = "operations_project_field_data",
 *   revision_table = "operations_project_revision",
 *   revision_data_table = "operations_project_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer project types",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "langcode" = "langcode",
 *     "bundle" = "project_type",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "collection" = "/admin/content/project",
 *     "add-form" = "/project/add/{project_type}",
 *     "add-page" = "/project/add",
 *     "canonical" = "/project/{project}",
 *     "edit-form" = "/project/{project}/edit",
 *     "delete-form" = "/project/{project}/delete",
 *   },
 *   bundle_entity_type = "project_type",
 *   field_ui_base_route = "entity.project_type.edit_form",
 * )
 */
class Project extends RevisionableContentEntityBase implements ProjectInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use RedirectDestinationTrait;
  use SitePropertyBundleFieldDefinitionsTrait;

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
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
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
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Description'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
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
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the project was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the project was last edited.'));

    return $fields;
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
    $operations['blank'] = ['title' => '', 'weight' => -100];
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
        'title' => t('Edit'),
        'weight' => 10,
        'url' => $this->ensureDestination($entity->toUrl('edit-form')),
      ];
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
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
}
