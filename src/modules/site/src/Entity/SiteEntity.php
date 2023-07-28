<?php

namespace Drupal\site\Entity;

use _PHPStan_978789531\Nette\PhpGenerator\Parameter;
use _PHPStan_978789531\Symfony\Contracts\Service\Attribute\Required;
use Drupal\backup_migrate\Core\Plugin\PluginCallerTrait;
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
use Drupal\Core\Field\Plugin\Field\FieldType\MapItem;
use Drupal\Core\Serialization\Yaml;
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
 *   bundle_label = @Translation("Site type"),
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
 *     "bundle" = "type",
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
 *   bundle_entity_type = "site_type",
 *   field_ui_base_route = "entity.site_type.edit_form",
 *   common_reference_target = TRUE,
 * )
 */
class SiteEntity extends RevisionableContentEntityBase implements SiteEntityInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  protected array $property_plugins;

  /**
   * @param $id string Site UUID. If not supplied, will load this site.
   * @inheritdoc
   */
  static public function load($id = null) {
    // If ID is not supplied, use site UUID.
    $site = parent::load($id ?: self::getSiteUuid());

    if ($site) {
      // Load plugin data.
      // See https://www.drupal.org/docs/drupal-apis/plugin-api/creating-your-own-plugin-manager
      $type = \Drupal::service('plugin.manager.site_property');
      $plugin_definitions = $type->getDefinitions();
      foreach ($plugin_definitions as $name => $plugin_definition) {
        $plugin = $type->createInstance($plugin_definition['id']);
        $data = $site->get('data')->getValue();
        $data['plugins'][$name] = $plugin->value();
        $site->set('data', $data);
        if ($site->hasField($name)) {
          $site->set($name, $plugin->value());
        }
      }
    }
    return $site;
  }

  public static function create(array $values = []) {

    // Load plugin data.
    // See https://www.drupal.org/docs/drupal-apis/plugin-api/creating-your-own-plugin-manager
    $type = \Drupal::service('plugin.manager.site_property');
    $plugin_definitions = $type->getDefinitions();
    foreach ($plugin_definitions as $name => $plugin_definition) {
      $plugin = $type->createInstance($plugin_definition['id']);
      $values['data']['properties'][$plugin->name()] = $plugin->value();
      $values[$plugin->name()] = $plugin->value();

    }

    return parent::create($values);
  }

  /**
   * Return the view array of the site entity.
   * @return array
   */
  public function view() {
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('site');
    $build = $view_builder->view($this);
    return $build;
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
   * {@inheritdoc}
   */
  public function save()
  {
    // @TODO: Only send when site is self, or site_manager can POST back (if we have API key).
    /** @var MapItem $settings */
    $settings = SiteDefinition::load('self')->get('settings');
    if (!empty($settings['send_on_save']) && !$this->no_send) {
      // send() triggers this function again with "no_send", so we don't need to call saveConfig().
      $this->send();
    }
    else {

      // SaveConfig and state, THEN save entity so it stores the new values.
      $this->saveConfig();
      $this->saveState();

      // @TODO: Reload and save again so new config and states are included in the report.

      parent::save();
    }
  }

  /**
   * Save drupal config items that are listed in self::config_overrides.
   */
  public function saveConfig() {
    if (!$this->isSelf()) {
      return;
    }
    $site_entity = $this;
    $site_config = SiteDefinition::load('self');
    $allowed_configs = $site_config->get('configs_allow_override');
    $config_overrides = $site_entity->config_overrides->getValue();
    $revision_url = $this->toUrl('canonical', ['absolute'=>true])->toString() . '/revisions/' . $site_entity->vid->value . '/view';

    $config_factory = \Drupal::configFactory();
    if (!empty($config_overrides[0])) {
      $config_overrides = $config_overrides[0];

      \Drupal::state()->set('site_config_events_disable', TRUE);
      foreach ($allowed_configs as $config_slug) {
        $slugs = explode(':', $config_slug);
        $config_name = $slugs[0];
        $config = $config_factory->getEditable($config_name);

        // If config override was found...
        if (!empty($config_overrides[$config_name])) {

          // If allowed config contains a key...
          $config_key = $slugs[1];
          if ($config_key && isset($config_overrides[$config_name][$config_key])) {
            $config_value = $config_overrides[$config_name][$config_key];
            if ($config_value) {
              $config
                ->set($config_key, $config_value)
                ->save()
              ;

              \Drupal::logger('site')->info('Site configuration (:config) set from Site entity: :url', [
                ':url' => $revision_url,
                ':config' => "{$config_name}: {$config_key}: " . Yaml::encode($config_value),
              ]);
            }
          }
          else {
            if ($config_item = $config_overrides[$config_name]) {
              foreach ($config_item as $config_key => $config_value) {
                $config
                  ->set($config_key, $config_value)
                  ->save()
                ;

                \Drupal::logger('site')->info('Site configuration (:config) set from Site entity: :url', [
                  ':url' => $revision_url,
                  ':config' => "{$config_name}: {$config_key}: " . Yaml::encode($config_value),
                ]);
              }
            }
          }
        }
      }
      \Drupal::state()->delete('site_config_events_disable');
      // @TODO: If the site title changed, update the entity.
    }

    // @TODO: Set State

  }

  /**
   * Save drupal state items that are listed in self::config_overrides.
   */
  public function saveState() {
    if (!$this->isSelf()) {
      return;
    }

    $site_entity = $this;
    $site_config = SiteDefinition::load('self');
    $allowed_states = $site_config->get('states_allow_override');
    $state_overrides = $site_entity->state_overrides->first()->value ?? [];
    $revision_url = $this->toUrl('canonical', ['absolute'=>true])->toString() . '/revisions/' . $site_entity->vid->value . '/view';
    if (!empty($state_overrides)) {
      foreach ($allowed_states as $state_name) {

        // If config override was found...
        if (!empty($state_overrides[$state_name])) {
          \Drupal::state()->set($state_name, $state_overrides[$state_name]);
          \Drupal::logger('site')->info('Site state (:state) set from Site entity: :url', [
            ':state' => "{$state_name}",
            ':url' => $revision_url,
          ]);
        }
      }
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
      ->setReadOnly(true)
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
        'type' => 'uri_link',
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
        'type' => 'number_integer',
        'label' => 'inline',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
    ;

     $fields['reason'] = BaseFieldDefinition::create('map')
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
    $fields['state_overrides'] = BaseFieldDefinition::create('map')
      ->setRevisionable(TRUE)
      ->setLabel(t('Site State Overrides'))
      ->setDescription(t('A Yaml map of Drupal states to apply to this site. See https://www.drupal.org/docs/8/api/state-api/overview'))
      ->setRequired(FALSE)
    ;

    // See https://www.drupal.org/docs/drupal-apis/plugin-api/creating-your-own-plugin-manager
    $type = \Drupal::service('plugin.manager.site_property');
    $plugin_definitions = $type->getDefinitions();
    foreach ($plugin_definitions as $name => $plugin_definition) {
      $plugin = $type->createInstance($plugin_definition['id']);

      if (method_exists(get_class($plugin), 'baseFieldDefinitions')) {
        $plugin->baseFieldDefinitions($entity_type, $fields);
      }
    }

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
   * Load the site entity for the current site.
   * @return SiteEntity
   */
  public static function loadSelf() {
    return self::load();
  }

  /**
   * {@inheritdoc}
   */
  public function revisionIds(SiteEntityInterface $site = null)
  {
    if (empty($site)) {
      $site = $this;
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

    $site_definition = SiteDefinition::load('self');
    $site_entity = SiteEntity::loadSelf();
    $settings = $site_definition->get('settings');

    if (empty($settings['send_destinations'])) {
      \Drupal::messenger()->addError('There are no send destinations configured. Unable to send Site data.');
      return;
    }

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

            // If field is in remote fields, don't send it so we don't alter it.
            $remote_fields = $site_definition->get('fields_allow_override');
            if (in_array($field_id, $remote_fields)) {
              continue;
            }

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

        $response = $client->post($url, [
          'headers' => [
            'Accept' => 'application/json',
          ],
          'json' => $payload
        ]);

        $response_entity_data = Json::decode($response->getBody()->getContents());
        if (!is_array($response_entity_data) || empty($response_entity_data['site_uuid'][0]['value'])) {
          throw new \Exception(t('Response from server was empty: ' . print_r($response_entity_data, 1)));
        }
        elseif ($response_entity_data['site_uuid'][0]['value'] != $site_entity->id()) {
          throw new \Exception(t('Site report is unable to be saved because the received site UUID does not match this site. Received: :response_uuid. Expected: :self_uuid. To allow saving reports locally, disable "Send on Save" or fix the problem with the server.', [
            ':response_uuid' => $response_entity_data['site_uuid'][0]['value'],
            ':self_uuid' => $site_entity->id(),
          ]));
        }
        else {
          \Drupal::messenger()->addStatus('Site report was sent successfully.');
        }

        foreach ($response_entity_data as $field => $value) {
          if ($this->hasField($field) && $field != 'site_uuid') {
            $this->set($field, $value);
          }
        }

        // Save, but block sending again.
        $this->setNewRevision();
        $this->vid = null;
        $this->revision_timestamp = \Drupal::time()->getCurrentTime();
        $this->revision_log = $this->revision_log->value . ' - ' . t('Site data returned from :url', [
          ':url' => $url,
        ]);
        $this->no_send = true;
        $this->save();

        return $this;

      } catch (GuzzleException $e) {
        if ($e->hasResponse()) {
          \Drupal::messenger()->addError(t('There was an error when posting to the remote server: :message', [
            ':message' => $e->getMessage(),
          ]));

          return $e->getResponse();
        } else {
          \Drupal::messenger()->addError(t('Could not connect to server: :message', [
            ':message' => $e->getMessage(),
          ]));
          return null;
        }
      } catch (\Exception $e) {
          \Drupal::messenger()->addError($e->getMessage());
          return null;
      }
    }
  }


  public function getStateClass() {
    return SiteDefinition::getStateClass($this->state->value);
  }

  /**
   * Return the API URL field, if empty, the Site URI field
   * @return Url|void
   */
  public function getSiteApiLink() {

    if (!empty($this->api_key)) {
      $uri = $this->api_uri->value ?: $this->site_uri->value;

      // @TODO: Find out how to get the SiteAPIResource URI.
      $url = Url::fromUri($uri . '/api/site/data')
        ->setOption('query', [
          'api-key' => $this->api_key->value,
        ])
        ->setOption('absolute', true)
      ;
      return $url;
    }
  }
}
