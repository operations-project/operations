<?php

namespace Drupal\site\Entity;

use _PHPStan_978789531\Nette\PhpGenerator\Parameter;
use _PHPStan_978789531\Symfony\Contracts\Service\Attribute\Required;
use Composer\Autoload\ClassLoader;
use Drupal\backup_migrate\Core\Plugin\PluginCallerTrait;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\MapItem;
use Drupal\Core\Link;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\jsonapi\Controller\EntityResource;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\Normalizer\ResourceObjectNormalizer;
use Drupal\site\Entity\Bundle\SiteManangerSiteBundle;
use Drupal\site\Event\SitePreSaveEvent;
use Drupal\site\SiteEntityTrait;
use Drupal\site\SitePropertyBundleFieldDefinitionsTrait;
use Drupal\site\SiteSelf;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\site\SiteEntityInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\HeaderBag;

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
 *       "default" = "Drupal\site\Form\SiteForm",
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
 *     "id" = "sid",
 *     "uuid" = "uuid",
 *     "bundle" = "site_type",
 *     "revision" = "vid",
 *     "label" = "label",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/site",
 *     "add-form" = "/site/add/{site_type}",
 *     "add-page" = "/site/add",
 *     "canonical" = "/site/{site}",
 *     "edit-form" = "/site/{site}/edit",
 *     "refresh" = "/site/{site}/refresh",
 *     "delete-form" = "/site/{site}/delete",
 *     "version_history" = "/site/{site}/history",
 *     "revision" = "/site/{site}/history/{site_revision}/view",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   bundle_entity_type = "site_type",
 *   field_ui_base_route = "entity.site_type.edit_form",
 *   common_reference_target = TRUE,
 *   constraints = {
 *     "SiteUniqueUrl" = {},
 *     "SiteDrupalProjectExists" = {}
 *   }
 * )
 *
 * The "SiteDrupalProjectExists" only affects DrupalSiteBundles. Can we apply a
 * constraint to a bundle?
 *
 */
class SiteEntity extends RevisionableContentEntityBase implements SiteEntityInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use SiteEntityTrait;
  use RedirectDestinationTrait;
  use SitePropertyBundleFieldDefinitionsTrait;

  protected HeaderBag $headers;

  protected array $property_plugins;

  /**
   * @var bool True if the site was sent successfully.
   */
  public bool $sent = false;

  /**
   * @param $id string Site UUID. If not supplied, will load this site.
   * @inheritdoc
   */
  static public function load($id) {
    $site = parent::load($id);

    return $site;
  }

  /**
   * Determine if this is a live site.
   *
   * @TODO: Implement this for non-drupal sites.
   *
   * Right now, only Drupal sites have a way to determine the canonical URL of a site
   * because they have the Drupal Project reference.
   *
   * Once we create Site Groups and SiteGroup Types we can have a "WebApp" Site Group Type that has canonical URL field as well.
   *
   * @return bool
   */
  public function isCanonical($url = null) {
    return false;
  }

  /**
   * Load all site managers for this site. Just a list of all 'site_manager' sites.
   *
   * @return array|SiteManangerSiteBundle
   */
  public function loadSiteManagers() {
    $site_managers = [];
    $site_manager_settings = \Drupal::configFactory()->get('site.settings')->get('site_manager');
    if (!empty($site_manager_settings['api_url'])) {
      $site_managers[] = SiteEntity::create([
        'site_type' => 'site_manager',
        'site_uri' => $site_manager_settings['api_url'],
        'api_url' => $site_manager_settings['api_url'],
        'hostname' => $site_manager_settings['api_url'],
        'api_key' => $site_manager_settings['api_key'],
      ]);
    }
    $site_manager_ids = \Drupal::entityQuery('site')
      ->condition('site_type', 'site_manager')
      ->condition('status', 1)
      ->execute() ?? [];

    foreach ($site_manager_ids as $site_manager_id) {
      $site_managers[] = SiteEntity::load($site_manager_id);
    }
    return $site_managers;
  }

  static public function getPluginData() {
    $site_definition = SiteDefinition::load('self');
    $type = \Drupal::service('plugin.manager.site_property');
    $plugin_definitions = $type->getDefinitions();
    $worst_plugin_state = self::SITE_OK;
    $plugin_data = [];
    foreach ($plugin_definitions as $name => $plugin_definition) {
      $plugin = $type->createInstance($plugin_definition['id']);

      $plugin_data['properties'][$name] = [
        'value' => $plugin->value(),
      ];

      if (method_exists($plugin, 'state')) {
        $plugin_state = $plugin->state($site_definition);
        $plugin_data['properties'][$name]['state'] = $plugin_state;

        if ($plugin_state > $worst_plugin_state) {
          $worst_plugin_state = $plugin_state;
        }
      }
    }
    $plugin_data['state'] = $worst_plugin_state;
    $plugin_data['reason'] = $site_definition->get('reason');
    return $plugin_data;
  }

  /**
   * Add item to the state reason build array.
   * @param array $build
   * @return void
   */
  public function addReason($value) {
    $reasons = $this->reason->getValue();
    $reasons[] = $value;
    $this->reason->setValue($reasons);
  }

  /**
   * Add item to the data property.
   * @param array $build
   * @return void
   */
  public function addData($key, $value) {
    $data = $this->data->value;
    $data[$key] = $value;
    $this->set('data', $data);
  }

  /**
   * Load the site entity for the current site.
   * @return SiteEntity
   */
  public static function loadSelf() {
    return self::loadBySiteUrl(static::getUri());
  }

  /**
   * @param $id string Site UUID. If not supplied, will load this site.
   * @inheritdoc
   */
  static public function loadBySiteUrl($site_url) {

    $url_host = parse_url($site_url, PHP_URL_HOST);
    $sites = \Drupal::entityTypeManager()
      ->getStorage('site')
      ->loadByProperties([
        'hostname' => [$url_host],
      ])
    ;
    $site = array_shift($sites);
    if ($site) {
      return static::load($site->id());
    }
  }

  public static function create(array $values = []) {
//
//    // Load plugin data.
//    $plugin_data = static::getPluginData();
//    $values['state'] = $plugin_data['state'];
//    $values['reason'] = $plugin_data['reason'];
//
//    $values['data']['properties'] = $plugin_data['properties'];
//    $values += $plugin_data['properties'];
    return parent::create($values);
  }

  /**
   * Return the view array of the site entity.
   * @return array
   */
  public function view($mode = 'full') {
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('site');
    $build = $view_builder->view($this, $mode);
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

    if ($this->isSelf()) {
      \Drupal::service('site.self')->prepareEntity($this);
    }
    else {
      $this->getRemote();
    }
//
//    // Dispatch site presave event.
//    $event = new SitePreSaveEvent($this);
//    $event_dispatcher = \Drupal::service('event_dispatcher');
//    $event_dispatcher->dispatch($event, SitePreSaveEvent::SITE_PRESAVE);

  }

  /**
   * {@inheritdoc}
   */
  public function save()
  {

    // Always set a new revision with create timestamp set to Now. (After property generation/retrieval)
    $this->setNewRevision();
    $this->setRevisionCreationTime(\Drupal::time()->getCurrentTime());

    // Normalize URL.
    if (!empty($this->site_uri->getValue())) {
      $url = parse_url($this->site_uri->value);
      $url_host = $url['host'];

      // If no hostname, set from URI.
      if (empty($this->hostname->getValue())) {
        $this->hostname->setValue($url_host);
      }

      // If no label, parse URL.
      if (empty($this->label->getValue())) {
        $this->label->setValue($url_host);
      }
    }

    /** @var MapItem $settings */
    $settings = \Drupal::config('site.settings');
    $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1];

    // Don't send if saving frmo JSON API.
    if ($caller['class'] != EntityResource::class && $this->isSelf() && !empty($settings->get('site_manager')['send_on_save']) && !$this->no_send) {
      // send() triggers this function again with "no_send", so we don't need to call saveConfig().
      try {
        $this->send();
      }
      catch (\Exception $e) {
        throw new EntityStorageException($e->getMessage());
      }
    }
    else {

      // SaveConfig and state, THEN save entity so it stores the new values.
      if ($this->isSelf()) {
        $this->saveConfig();
        $this->saveState();
      }

      // Set changed as well.
      $this->changed = time();

      // @TODO: Reload and save again so new config and states are included in the report.

      parent::save();
      \Drupal::logger('site')->info('Site entity saved from {url}: {entity}', [
        'url' => \Drupal::request()->getUri(),
        'entity' => $this->toUrl('canonical', ['absolute' => true])->toString(),
      ]);
    }
  }

  /**
   * Saves the current site's entity with new parameters.
   *
   * @param $revision_log
   * @param $no_send
   * @return void
   */
  static public function saveRevision($revision_log = '', $no_send = false) {
//    $site_entity = SiteEntity::loadSelf();
//    if (!$site_entity) {
//      $site_entity = SiteEntity::create();
//    }
//    else {
//
//      // Load plugin data.
//      $plugin_data = static::getPluginData();
//      $site_entity->set('state', $plugin_data['state']);
//      $site_entity->set('reason', $plugin_data['reason']);
//      foreach ($plugin_data['properties'] as $name => $property) {
//        if ($site_entity->hasField($name)) {
//          $site_entity->set($name, $property['value']);
//        }
//      }
//    }
//
//    $site_entity->setRevisionLogMessage($revision_log);
//    $site_entity->setNewRevision();
//    $site_entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
//    $site_entity->no_send = $no_send;
//    $site_entity->save();
//    return $site_entity;
  }

  /**
   * Save drupal config items that are listed in self::config_overrides.
   */
  public function saveConfig() {

    // If this is not the site we are saving the entity for, or if there are
    // no overrides, do nothing.
    if (!$this->isSelf() || empty($site_entity->config_overrides)) {
      return;
    }

    $site_entity = $this;
    $site_config = \Drupal::service('config.factory')->get('site.settings');
    $allowed_configs = $site_config->get('configs_allow_override');
    $config_overrides = $site_entity->config_overrides->getValue();

    if (!empty($this->id())) {
      $revision_url = $this->toUrl('canonical', ['absolute'=>true])->toString() . '/revisions/' . $site_entity->vid->value . '/view';
    }
    else {
      $revision_url = Url::fromRoute('site.history');
    }

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

    // If this is not the site we are saving the entity for, or if there are
    // no overrides, do nothing.
    if (!$this->isSelf() || empty($site_entity->state_overrides)) {
      return;
    }

    $site_entity = $this;
    $site_config = SiteDefinition::load('self');
    $allowed_states = $site_config->get('states_allow_override');
    $state_overrides = $site_entity->state_overrides->first()->value ?? [];

    if (!empty($this->id())) {
      $revision_url = $this->toUrl('canonical', ['absolute'=>true])->toString() . '/revisions/' . $site_entity->vid->value . '/view';
    }
    else {
      $revision_url = Url::fromRoute('site.history');
    }

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
    $fields += static::revisionLogBaseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('A short string to display in links to this site, such as "dev" or "live".'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 100,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hostname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Primary Hostname'))
      ->setDescription(t('The primary hostname for this website, without a scheme or path.'))
      ->setRequired(true)
      ->addConstraint('UniqueField')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -100,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => -100,
      ])
    ;

    // @TODO Replace when content entity is ready.
//    $fields['site_definition'] = BaseFieldDefinition::create('entity_reference')
//      ->setLabel(t('Site Definition'))
//      ->setSetting('target_type', 'site_definition')
//      ->setRequired(true)
//      ->setDefaultValue(['self'])
//      ->setDisplayConfigurable('view', TRUE);

    $fields['site_uri'] = BaseFieldDefinition::create('uri')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setLabel(t('Site URLs'))
      ->setDescription(t('The URLs used for this site.'))
      ->addConstraint('SiteUniqueUrl')

      // @TODO: I'm going to do this in the SiteForm::addform() for now. Should
      // This should probably be done at the entity API level?
      ->setDefaultValueCallback(static::class . '::getDefaultUri')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -90,
      ])
      ->setDisplayConfigurable('view', TRUE);
    ;

    $fields['state'] = BaseFieldDefinition::create('list_integer')
      ->setSetting('allowed_values', [
        static::SITE_OK => t('OK'),
        static::SITE_INFO => t('OK (Info)'),
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
    ;

     $fields['reason'] = BaseFieldDefinition::create('map')
        ->setLabel(t('State Reason'))
        ->setRevisionable(TRUE)
        ->setDisplayConfigurable('view', TRUE);

    $fields['site_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Site Title'))
      ->setDescription(t('The title of this website.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => -100,
      ])
    ;

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Show this site in Site Manager.'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 100,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
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
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the site entity was created.'))
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
    // Only set default if no other entity with this uri exists.
    if (empty(SiteEntity::loadBySiteUrl(self::getUri()))) {
      return \Drupal::request()->getSchemeAndHttpHost();
    };
  }

  /**
   * Returns the default value for site audit report entity uri base field.
   *
   * @return string
   *   The site's hostname.
   */
  public static function getUri() {
    return \Drupal::request()->getSchemeAndHttpHost();
  }

  /**
   * @return string
   *   The site's hostname, not including https:// or trailing paths.
   */
  public static function getHostname() {
    return \Drupal::request()->getHost();
  }



  /**
   * Returns the default value for site audit report entity uri base field.
   *
   * @return string
   *   The site's hostname.
   */
  public static function getDefaultHostname() {
    // Only set default if no other entity with this uri exists.
    if (empty(SiteEntity::loadBySiteUrl(self::getUri()))) {
      return \Drupal::request()->getHost();
    };
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
   * Returns the site's title.
   *
   * @return string
   *   The site's uuid.
   */
  public static function getSiteTitle() {
    return \Drupal::config('system.site')->get('name');
  }

  /**
   * Return the root path of the site codebase.
   *
   * @return string
   */
  public static function getSiteRoot() {
    $reflection = new \ReflectionClass(ClassLoader::class);
    $vendorDir = dirname(dirname($reflection->getFileName()));
    return dirname($vendorDir);
  }

  /**
   * Load the site entity with the same UUID as this site.
   */
  public function isSelf() {
    $url_host = parse_url(static::getUri(), PHP_URL_HOST);
    return $this->hostname->value == $url_host;

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
        'SELECT [vid] FROM {' . $this->getEntityType()->getRevisionTable() . '} WHERE [sid] = :sid ORDER BY [vid] DESC',
        [':sid' => $site->id()]
    )->fetchCol();
  }

  /**
   * Sends entity to the configured remotes.
   * @return void
   */
  public function send() {

    // Prepare JSONAPI Entity
    $data = $this->toJsonApiArray();

    // Remove data that won't align with remote server.
    // I don't know why, but I am getting an error when api_url is sent:
    // There was a problem when saving the site: Unprocessable Entity 422: The attribute api_url does not exist on the site--drupal resource type..
    unset($data['relationships']);
    unset($data['attributes']['changed']);
    unset($data['attributes']['drupal_internal__sid']);

    // Don't PATCH these so the site manager sites don't lose it.
    unset($data['attributes']['api_key']);
    unset($data['attributes']['api_url']);

    // Load all Site Manager nodes.
    $managers = $this->loadSiteManagers();
    foreach ($managers as $site_manager) {

      if (empty($site_manager->api_url->value)) {
        throw new \Exception(t("No API URL found for site :@site.", [
          '@site' => $site_manager->site_uri->value,
        ]));
      }
      $base_url = trim($site_manager->api_url->value, '/');

      // Send this entity to that site_manager.
      $site_api_url = $base_url . $this->toJsonApiUrl()->toString();
      $site_api_key = $site_manager->api_key->value ?? '';

      $client = new Client([
        'allow_redirects' => TRUE,
      ]);
      $options = [
        'headers' => [
          'Accept' => 'application/vnd.api+json',
          'Content-type' => 'application/vnd.api+json',
          'api-key' => $site_api_key,
          'requester' => \Drupal::request()->getHost(),
        ],
        'json' => [
          'data' => $data,
        ]
      ];

      // Confirm site manager and site UUID via GET
      $remote_site_exists = false;
      try {
        $site_api_get_uri = $base_url . Url::fromRoute('site.api')->toString();
        $response = $client->get($site_api_get_uri, $options);
        $site_api_data = Json::decode($response->getBody()->getContents());

        // If remote site entity exists, set the local uuid.
        $remote_site_exists = !empty($site_api_data['requester']['site_entity']['id']);
        if (!empty($site_api_data['requester']['site_entity']['id']) && $site_api_data['requester']['site_entity']['id'] != $this->uuid()) {
          $this->setRevisionLogMessage(t('Updated site UUID from :orig to :new.', [
            ':orig' => $this->uuid(),
            ':new' => $site_api_data['requester']['site_entity']['id'],
          ]));
          $this->set('uuid', $site_api_data['requester']['site_entity']['id']);
          $site_api_url = $site_api_data['requester']['site_entity']['links']['self']['href'];

          // Reload JSON API data.
          $data = $this->toJsonApiArray();
          unset($data['attributes']['changed']);
          unset($data['attributes']['drupal_internal__sid']);
          unset($data['relationships']);

          // I don't know why, but I am getting an error when api_url is sent:
          // There was a problem when saving the site: Unprocessable Entity 422: The attribute api_url does not exist on the site--drupal resource type..
          unset($data['attributes']['api_url']);

          $options['json']['data'] = $data;
        }
      }
      catch (ClientException $e) {
        throw $e;
      }
      catch (\Exception $e) {
        throw new \Exception(t('Something else happened:') . $e->getMessage());
      }

      // If site exists, patch. If not, post.
      try {
        if ($remote_site_exists) {
          $site_api_url = $base_url . $this->toJsonApiUrl('individual')->toString();
          $response = $client->patch($site_api_url, $options);
        }
        else {
          $site_api_url = $base_url . $this->toJsonApiUrl('collection.post')->toString();
          $response = $client->post($site_api_url, $options);
        }
      }
      catch (ClientException $e) {
        $response_data = Json::decode($e->getResponse()->getBody()->getContents());
        $messages = SiteEntity::formatJsonApiErrors($response_data);
        throw new \Exception(implode(PHP_EOL, $messages));
      }
      catch (\Exception $e) {
        throw new \Exception(t('An unknown exception occurred when posting/patching the site entity @site to @url. The error was: @error', [
          '@site' => $this->toUrl('canonical', ['absolute' => true])->toString(),
          '@url' => $site_api_url,
          '@error' => $e->getMessage(),
        ]));
      }
      $response_entity_data = Json::decode($response->getBody()->getContents());
      \Drupal::logger('site')->info('Site Entity sent from {url}.', [
        'url' => $site_api_url,
      ]);

      $this->sent = true;
      foreach ($this->getFields() as $field_id => $field) {
        if (isset($response_entity_data['data']['attributes'][$field_id])) {

          // If JSONAPI worked, this wouldn't be needed.
          switch ($field_id) {
            case 'sid':
            case 'revision_timestamp':
            case 'revision_log':
            case 'reason':
            case 'state':
              continue(2);

            case 'created':
            case 'changed':
              $value = strtotime($response_entity_data['data']['attributes'][$field_id]);
              break;
            default:
              $value = $response_entity_data['data']['attributes'][$field_id];

          }
          $this->set($field_id, $value);
        }
      }

      $this->setRevisionLogMessage(t('Saving after receiving from :url', [
        ':url' => $site_api_url,
      ]));
      $this->no_send = true;
      $this->save();


      $api_url = $response_entity_data['data']['links']['self']['href'] ?? '';
//      \Drupal::messenger()->addStatus(t('The site was successfully connected. Data available at @link.', [
//        '@link' => Link::fromTextAndUrl($api_url, Url::fromUri($api_url))->toString(),
//      ]));

      // @TODO: Save managed_fields.

    }

    /****
     * @TODO: re-implement generic "send_destinations"
     */

  }

  /**
   * @return SiteEntityInterface
   */
  public function getRemote() {

    // Append reasons.
    $reasons = $this->get('reason')->get(0) ? $this->get('reason')->get(0)->getValue(): [];

    $worst_code = 0;
    $site_uri_data = [];
    foreach ($this->site_uri as $i => $site_uri_field) {
      $i = $site_uri_field->value;
      $site_uri_data[$i] = [
        'code' => 0,
        'headers' => [],
        'content' => '',
      ];
      $this_site_link = Link::fromTextAndUrl(SiteEntity::getUri(), Url::fromUri(SiteEntity::getUri()))->toString();
      try {
        $site_uri = $site_uri_field->value;
        $response = \Drupal::httpClient()->get($site_uri);

        // We know it was successful, otherwise there's an exception.
        $reason = [
          '#title' => t('HTTP Request for @url was successful.', [
            '@url' => Link::fromTextAndUrl($site_uri, Url::fromUri($site_uri))->toString(),
          ]),
          '#type' => 'item',
          '#markup' => t('The URL @url responded with @code @message when requested from @site.', [
            '@site' => $this_site_link,
            '@url' => Link::fromTextAndUrl($site_uri, Url::fromUri($site_uri))->toString(),
            '@code' => $response->getStatusCode(),
            '@message' => $response->getReasonPhrase(),
          ])
        ];
      } catch (ClientException $e) {
        $response = $e->getResponse();
        $reason = [
          '#title' => t('HTTP Request for @url failed with @code when requested from @site.', [
            '@site' => $this_site_link,
            '@url' => Link::fromTextAndUrl($site_uri, Url::fromUri($site_uri))->toString(),
            '@code' => $response->getStatusCode(),
          ]),
          '#type' => 'item',
          '#markup' => $e->getMessage(),
        ];
      } catch (\Exception $e) {
        $reason = [
          '#title' => t('HTTP Request for @url from @site failed for an unknown reason.', [
            '@url' => Link::fromTextAndUrl($site_uri, Url::fromUri($site_uri))->toString(),
            '@site' => $this_site_link,
          ]),
          '#type' => 'item',
          '#markup' => t('Request for URL @url failed: @message.', [
            '@message' => $e->getMessage(),
          ])
        ];
        $reasons[] = $reason;
        continue;
      }

      if ($response->getStatusCode() > $worst_code) {
        $worst_code = $response->getStatusCode();
      }

      $site_uri_data[$i] = [
        'code' => $response->getStatusCode(),
        'headers' => $response->getHeaders(),
        'content' => $response->getBody()->getContents(),
      ];
      $reasons[] = $reason;
    }

    $this->addData('site_uri', [
      'sites' => $site_uri_data,
      'worst_code' => $worst_code,
    ]);
    $this->get('reason')->set(0, $reasons);

//    $this->reason->setValue($reasons);

    switch ($worst_code) {
      case 200:
        $state = SiteEntity::SITE_OK;
        break;
      case 403:
        $state = SiteEntity::SITE_WARN;
        break;
      default:
        $state = SiteEntity::SITE_ERROR;
        break;
    }

    // Set state if higher.
    if ($this->state->value < $state) {
      $this->state->setValue($state);
    }

    $this->headers = new HeaderBag($site_uri_data[$this->site_uri->value]['headers']);

    return $this;
  }

  /**
   * Return the API URL field, if empty, the Site URI field
   * @return Url|void
   */
  public function getSiteApiLink() {

    if (!empty($this->api_key)) {
      $uri = $this->api_url->value ?: $this->site_uri->value;

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

  /**
   * Retrieve a property plugin directly.
   *
   * @param $plugin_name
   * @return mixed
   */
  static public function getPropertyPlugin($plugin_name) {
    $type = \Drupal::service('plugin.manager.site_property');
    $plugin = $type->createInstance($plugin_name);
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    $entity = $this;

    if (!$entity->id()) {
      return [];
    }
    $operations = [];
    $operations['blank'] = ['title' => '', 'weight' => -100];
    $operations += $this->getDefaultOperations($entity);
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
    if ($entity->access('update') && $entity->hasLinkTemplate('refresh')) {
      $operations['refresh'] = [
        'title' => t('Refresh data'),
        'weight' => 10,
        'url' => $this->ensureDestination($entity->toUrl('refresh')),
      ];
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
      $operations['delete'] = [
        'title' => t('Delete'),
        'weight' => 100,
        'url' => $this->ensureDestination($entity->toUrl('delete-form')),
      ];
    }

    // Load all SiteAction plugins.
    foreach (\Drupal::service('plugin.manager.site_action')->getDefinitions() as $plugin_definition) {
      $plugin = \Drupal::service('plugin.manager.site_action')->createInstance($plugin_definition['id'], [
        'site' => $this,
      ]);
      $operation = "action_" . $plugin->getPluginId();
      $url = Url::fromRoute('entity.site.site_actions', [
        'site' => $this->id(),
        'plugin_id' => $plugin->getPluginId(),
      ]);
      if ($plugin->isOperation() && $plugin->access()) {
        $operations[$operation] = [
          'title' => $plugin->label(),
          'url' => $this->ensureDestination($url),
          'weight' => 20,
        ];
      }
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
   * Return the JSON API URL for this entity.
   * @param $options
   * @return Url
   */
  public function toJsonApiUrl($route_item = 'individual') {
    $url = Url::fromRoute("jsonapi.{$this->entityTypeId}--{$this->bundle()}.{$route_item}");
    if ($route_item == 'individual') {
      $url->setRouteParameter('entity', $this->uuid());
    }
    return $url;
  }

  /**
   * Generate a JSON:API object.
   *
   * @return array
   */
  public function toJsonApiArray() {
    # @see EntityResource
    $resource_object = $this->toResourceObject();
    $serializer = \Drupal::service('jsonapi.serializer');
    $cacher =  \Drupal::service('jsonapi.normalization_cacher');
    $normalizer = new ResourceObjectNormalizer($cacher);
    $normalizer->setSerializer($serializer);
    $data = $normalizer->normalize($resource_object, 'api_json', [
      'account' => \Drupal::currentUser()->getAccount(),
    ]);
    return $data->getNormalization();
  }

  public function toResourceObject() {
    $resource_type = \Drupal::service('jsonapi.resource_type.repository')->get($this->getEntityTypeId(), $this->bundle());
    return ResourceObject::createFromEntity($resource_type, $this);
  }

  /**
   * Turn JSONAPI "errors" response into an array of strings.
   *
   * @param array $jsonapi_response_data
   * @return array
   */
  static public function formatJsonApiErrors(array $jsonapi_response_data) {
    $items = [];
    if (!empty($jsonapi_response_data['errors'])) {
      foreach ($jsonapi_response_data['errors'] as $error) {
        $items[] = strtr('@title @status: !detail.', [
          '@title' => $error['title'],
          '@status' => $error['status'],
          '!detail' => $error['detail'],
        ]);
      }
    }
    return $items;
  }
}
