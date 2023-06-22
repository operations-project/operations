<?php

namespace Drupal\site\Entity;

use _PHPStan_978789531\Nette\PhpGenerator\Parameter;
use _PHPStan_978789531\Symfony\Contracts\Service\Attribute\Required;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\site\SiteEntityTrait;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\site\SiteEntityInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;

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
 *     "edit-form" = "/admin/reports/site/{site}/edit",
 *     "delete-form" = "/admin/reports/site/{site}/delete",
 *     "version-history" = "/admin/reports/site/{site}/revisions",
 *     "revision" = "/admin/reports/site/{site}/revisions/{site_revision}/view",
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
  public function save() {
    parent::save();

    $site_entity = $this;
    $site_config = SiteDefinition::load('self');
    $allowed_configs = $site_config->get('configs_allow_override');
    $config_overrides = $site_entity->config_overrides->getValue();

    $config_factory = \Drupal::configFactory();
    if ($this->isSelf() && !empty($config_overrides[0])) {
      $config_overrides = $config_overrides[0];
      foreach ($allowed_configs as $config_slug) {
        $slugs = explode(':', $config_slug);
        $config_name = $slugs[0];
        $config = $config_factory->getEditable($config_name);

        // If config override was found...
        if (!empty($config_overrides[$config_name])) {

          // If allowed config contains a key...
          if ($config_key = $slugs[1]) {
            if ($config_value = $config_overrides[$config_name][$config_key]) {
              $config
                ->set($config_key, $config_value)
                ->save()
              ;
            }
          }
          else {
            if ($config_item = $config_overrides[$config_name]) {
              foreach ($config_item as $key => $value) {
                $config
                  ->set($key, $value)
                  ->save()
                ;
              }
            }
          }
        }
      }
      // @TODO: If the site title changed, update the entity.
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
//      ->setDisplayOptions('form', [
//        'type' => 'string_textfield',
//      ])
//      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['site_uuid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Site UUID'))
      ->setDescription(t('The Drupal site UUID.'))
      ->setRequired(true)
      ->setDefaultValueCallback(static::class . '::getSiteUuid')
//      ->setDisplayOptions('form', [
//        'type' => 'string_textfield',
//      ])
//      ->setDisplayConfigurable('form', TRUE)
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
//      ->setDisplayOptions('form', [
//        'type' => 'string_textfield',
//      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'uri',
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
//      ->setDisplayOptions('form', [
//        'type' => 'options_select',
//        'weight' => -1,
//      ])
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
        'type' => 'string_textarea',
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
    $fields['config_overrides'] = BaseFieldDefinition::create('map')
      ->setRevisionable(TRUE)
      ->setLabel(t('Site Config Overrides'))
      ->setDescription(t('A Yaml map of Drupal configuration to apply to this site.'))
      ->setRequired(FALSE)
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
  public function isSelf() {
    return static::getSiteUuid() == $this->site_uuid->value;
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

  /**
   * Sends entity to the configured remotes.
   * @return void
   */
  public function send() {
    if (empty($this->toArray()['settings'][0]['send_destinations'])) {
      \Drupal::messenger()->addError('There are no send destinations configured. Unable to send Site data.');
      return;
    }
    $settings = $this->toArray()['settings'][0];

    // Validate URLs
    $urls = explode("\n", $settings['send_destinations']);
    foreach (array_filter($urls) as $url) {
      $url = trim($url);

      try {
        $client = new Client([
          'base_url' => $url,
          'allow_redirects' => TRUE,
        ]);

        $payload = [];
        foreach ($this->getFields() as $field_id => $field) {

          $first = $field->first();
          if ($first) {
            $field_data = $first->getValue();
            $field_key = $first->getDataDefinition()->getMainPropertyName();
            // If there is no main property name, pass the entire thing.
            // ie. for the data field.
            if (empty($field_key)) {
              $payload[$field_id] = $field_data;
            } else {
              $payload[$field_id] = $field_data[$field_key];
            }
          }
        }

        \Drupal::moduleHandler()->alter('site_audit_remote_payload', $payload);
        $payload['sent_from'] = $_SERVER['HTTP_HOST'];

        // @TODO: Fix the SiteApiResource to accept POST.
        $url = Url::fromUri($url, [
          'query' => $payload,
          'absolute' => true,
        ]);

        $response = $client->get($url->toString(), [
          'headers' => [
            'Accept' => 'application/json',
          ],
        ], $payload);

        \Drupal::messenger()->addStatus('Site report was sent successfully.');

        $response_entity_data = Json::decode($response->getBody()->getContents());
        $uuid = $response_entity_data['site_uuid'][0]['value'];
        $site_entity = SiteEntity::load($uuid);
        $site_entity->setNewRevision();
        $site_entity->revision_log = t('Set from remote response');

        foreach ($response_entity_data as $field => $value) {
          $site_entity->set($field, $value);
        }

        $site_entity->save();

        // @TODO: Save locally.
        return true;

      } catch (GuzzleException $e) {
        if ($e->hasResponse()) {
          \Drupal::messenger()->addError(t('There was an error when posting to the remote server: :message', [
            ':message' => $e->getMessage(),
          ]));

          return $e->getResponse();
        } else {
          \Drupal::messenger()->addError(t('Could not connect to server.'));
          return null;
        }
      }
    }
  }
}
