<?php

namespace Drupal\site\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\SiteEntityTrait;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\site\SiteEntityInterface;

/**
 * Defines the site entity class.
 *
 * @ContentEntityType(
 *   id = "site",
 *   label = @Translation("Site"),
 *   label_collection = @Translation("Sites"),
 *   label_singular = @Translation("site"),
 *   label_plural = @Translation("sites"),
 *   label_count = @PluralTranslation(
 *     singular = "@count site",
 *     plural = "@count sites",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\site\SiteListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\site\SiteAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\site\Form\SiteForm",
 *       "edit" = "Drupal\site\Form\SiteForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "operations_site",
 *   data_table = "operations_site_data",
 *   revision_table = "operations_site_revision",
 *   revision_data_table = "operations_site_revision_data",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer sites",
 *   entity_keys = {
 *     "id" = "site_uuid",
 *     "revision" = "vid",
 *     "label" = "site_title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/reports/site/{site}",
 *     "edit-form" = "/site/{site}/edit",
 *     "version-history" = "/site/{site}/revisions",
 *     "revision" = "/site/{site}/history/{site_revision}/view",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   field_ui_base_route = "entity.site_type.edit_form",
 *   common_reference_target = TRUE,
 * )
 */
class SiteEntity extends RevisionableContentEntityBase implements SiteEntityInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
//  use SiteEntityTrait;
//
//  public function save() {
//    dsm($this->toArray(), 'data');
//    dsm($this->fieldDefinitions, 'field def');
//    parent::save();
//  }

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

    $fields['site_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Site Title'))
      ->setDescription(t('The title of this Drupal site.'))
      ->setRequired(true)
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback(static::class . '::getDefaultSiteTitle')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['site_uuid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Site UUID'))
      ->setDescription(t('The Drupal site UUID.'))
      ->setRequired(true)
      ->setReadOnly(true)
      ->setDefaultValueCallback(static::class . '::getSiteUuid')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['git_remote'] = BaseFieldDefinition::create('string')
        ->setLabel(t('Git Remote URL'))
        ->setRevisionable(TRUE)
        ->setDisplayOptions('form', [
            'type' => 'string_textfield',
            'weight' => 10,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('view', [
            'type' => 'string',
            'label' => 'inline',
            'weight' => 10,
        ])
        ->setDisplayConfigurable('view', TRUE);

    $fields['site_uri'] = BaseFieldDefinition::create('uri')
      ->setRevisionable(TRUE)
      ->setLabel(t('Site URI'))
      ->setDescription(t('The URI of the site this report was generated for.'))
      ->setRequired(TRUE)
      ->setDefaultValueCallback(static::class . '::getDefaultUri')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'uri',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('view', TRUE);
    ;

    $fields['state'] = BaseFieldDefinition::create('list_integer')
      ->setSetting('allowed_values', [
        static::SITE_OK => t('OK'),
        static::SITE_INFO => t('Information Available'),
        static::SITE_WARN => t('Warning'),
        static::SITE_ERROR => t('Error'),
      ])
      ->setLabel(t('Site State'))
      ->setDescription(t('The overall state of the site. OK, INFO, WARN, ERROR'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValue(static::SITE_INFO)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'integer',
        'label' => 'inline',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
    ;

    $fields['reason'] = BaseFieldDefinition::create('text_long')
        ->setLabel(t('State Reason'))
        ->setRevisionable(TRUE)
        ->setDisplayOptions('view', [
            'type' => 'text_default',
            'label' => 'above',
            'weight' => 10,
        ])
        ->setDisplayOptions('view', [
            'type' => 'text_default',
            'label' => 'above',
            'weight' => 10,
        ])
        ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
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
      ->setLabel(t('Description'))
      ->setRevisionable(TRUE)
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
      ->setDescription(t('The time that the site entity was created.'))
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
      ->setDescription(t('The time that the site entity was last updated.'));

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setRevisionable(TRUE)
      ->setLabel(t('Site Data'))
      ->setDescription(t('A map of arbitrary data about the site.'))
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE)
    ;
    $fields['settings'] = BaseFieldDefinition::create('map')
      ->setRevisionable(TRUE)
      ->setLabel(t('Site Settings'))
      ->setDescription(t('A map of arbitrary settings for the site. Use for editable things.'))
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE)
    ;
    return $fields;
  }

  /**
   * Returns the default value for site audit report entity uri base field.
   *
   * @return string
   *   The site's hostname.
   */
  public static function getDefaultUri() {
    return \Drupal::request()->getSchemeAndHttpHost();
  }

  /**
   * Returns the default value for site audit report entity uri base field.
   *
   * @return string
   *   The site's title.
   */
  public static function getDefaultSiteTitle() {
    return \Drupal::config('system.site')->get('name');
  }

  /**
   * Returns the site's UUID.
   *
   * @return string
   *   The site's uuid.
   */
  public static function getSiteUuid() {
    return \Drupal::config('system.site')->get('uuid');
  }

  /**
   * Load the site entity with the same UUID as this site.
   */
  public static function loadSelf() {
    return parent::load(static::getSiteUuid());
  }

  /**
   * {@inheritdoc}
   */
  public function revisionIds(SiteEntityInterface $site = null)
  {
    if (empty($site)) {
      $site = self::loadSelf();
    }

    return \Drupal::database()->query(
        'SELECT [vid] FROM {' . $this->getEntityType()->getRevisionTable() . '} WHERE [site_uuid] = :site_uuid ORDER BY [vid]',
        [':site_uuid' => $site->id()]
    )->fetchCol();
  }
}
