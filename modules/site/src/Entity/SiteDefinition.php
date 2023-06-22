<?php

namespace Drupal\site\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\site\SiteDefinitionInterface;
use Drupal\site\Event\SiteGetState;
use Drupal\site\SiteEntityTrait;

/**
 * Defines the site definition entity type.
 *
 * @ConfigEntityType(
 *   id = "site_definition",
 *   label = @Translation("Site Definition"),
 *   label_collection = @Translation("Site Definitions"),
 *   label_singular = @Translation("site definition"),
 *   label_plural = @Translation("site definitions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count site definition",
 *     plural = "@count site definitions",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\site\SiteDefinitionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\site\Form\SiteDefinitionForm",
 *       "edit" = "Drupal\site\Form\SiteDefinitionForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "site_definition",
 *   admin_permission = "administer site_definition",
 *   links = {
 *    "collection" = "/admin/operations/sites/definitions",
 *    "edit-form" = "/admin/reports/site/{site_definition}",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "canonical_url" = "canonical_url",
 *     "git_remote" = "git_remote",
 *     "description" = "description",
 *   },
 *   config_export = {
 *     "id",
 *     "canonical_url",
 *     "git_remote",
 *     "description",
 *     "configs_load",
 *     "configs_allow_override",
 *     "states_load",
 *     "states_allow_override",
 *     "state_factors",
 *     "settings"
 *   }
 * )
 */
class SiteDefinition extends ConfigEntityBase implements SiteDefinitionInterface {

  use StringTranslationTrait;
  use SiteEntityTrait;

  /**
   * The site definition ID.
   *
   * @var string
   */
  protected string $id;

  /**
   * Configuration properties loaded from $configs_load
   *
   * @var string
   */
  protected array $config;

  /**
   * @var array An arbitrary array of settings. Modules can alter the form and save more data.
   */
  protected array $settings;

  /**
   * Sets label from site title
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->setDynamicProperties();
    $this->getConfig();
    $this->getDrupalStates();
  }

  public function setDynamicProperties() {
    if ($this->isSelf()) {
      $this->site_title = \Drupal::config('system.site')->get('name');
      $this->site_uuid = \Drupal::config('system.site')->get('uuid');
      $this->site_uri = \Drupal::request()->getSchemeAndHttpHost();

      $this->determineState();
    }
  }
  /**
   * Parse states_load and load the state values into the SiteDefinition entity "data: state" property.
   * @return void
   */
  public function getDrupalStates() {
    if (isset($this->states_load)) {
      foreach ($this->states_load as $state_string) {
        $this->data['states'][$state_string] = \Drupal::state()->get($state_string);
      }
    }
  }

  /**
   * Parse configs_load and load the config values into the SiteDefinition entity "data: config" property.
   * @return void
   */
  public function getConfig() {
    if (isset($this->configs_load)) {
      foreach ($this->configs_load as $config_string) {
        $config_items = explode(':', trim($config_string));
        $config_name = $config_items[0];
        $config_key = $config_items[1] ?? null;
        if ($config_key) {
          $value = \Drupal::config($config_name)->get($config_key);
          $this->data['config'][$config_name] = [
            $config_key => $value,
          ];
        }
        else {
          $value = \Drupal::config($config_name)->get();
          $this->data['config'][$config_name] = $value;
        }
      }
    }
  }

  /**
   * Is this site definition for this site? Used for detectable fields.
   * @return bool
   */
  public function isSelf() {
    return $this->id() == 'self';
  }

  /**
   * Return a build array with a nice summary of the site.
   * @return array[]
   */
  public function view() {
    $entity_object = $this->toEntity();

    $label_inline = ['label' => 'inline'];
    $label_hidden = ['label' => 'hidden'];

    $build['state'] = $entity_object->state->view($label_inline);
    $build['state']['0']['reason'] = $entity_object->reason->view($label_hidden);
    $build['state']['0']['reason']['#prefix'] = '<blockquote>';
    $build['state']['0']['reason']['#suffix'] = '</blockquote>';
    $build['state']['0']['reason'][0]['#format'] = 'basic_html';
    $build['state']['0']['reason']['#access'] = !empty($entity_object->reason->value);

    $build['site_title'] = $entity_object->site_title->view($label_inline);
    $build['site_uri'] = $entity_object->site_uri->view($label_inline);
    $build['site_uuid'] = $entity_object->site_uuid->view($label_inline);
//    $build['data'] = $entity_object->data->view($label_inline);

    if (!empty($this->data['config'])) {
      $build['config'] = [
        '#type' => 'details',
        '#title' => t('Site Configuration'),
        '#description' => $this->t('This data is configured with the "Configuration items to load." setting. It represents live configuration of this site.'),
      ];
      foreach ($this->data['config'] as $config => $data) {
        $build['config'][$config] = [
          '#type' => 'item',
          '#title' => $config,
          '#markup' => '<pre>' . Yaml::encode($data) . '</pre>',
        ];
      }
    }
    if (!empty($this->data['states'])) {
      $build['states'] = [
        '#type' => 'details',
        '#title' => t('State Values'),
        '#description' => $this->t('This data is configured with the "State items to load." setting. It represents live StateAPI values of this site.'),
      ];
      foreach ($this->data['states'] as $state => $data) {
        $build['states'][$state] = [
          '#type' => 'item',
          '#title' => $state,
          '#markup' => '<pre>' . Yaml::encode($data) . '</pre>',
        ];
      }
    }


    return $build;
  }

  /**
   * Dispatch the site_get_state event to determine the state of the site.
   */
  public function determineState() {

    // Dispatch event.
    $event = new \Drupal\site\Event\SiteGetState($this);
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch($event, SiteGetState::GET_STATE);

    // Set state from event siteDefinition. If not set, assume site is ok.
    $this->state = $event->siteDefinition->get('state') ?? self::SITE_OK;
    $this->reason = $event->siteDefinition->get('reason') ?? '';
  }

  /**
   * Transform SiteDefinition data to SiteEntity data.
   * @return array
   */
  public function toEntityData() {
    $data = [
        'type' => 'default',
        'site_uuid' => $this->site_uuid,
        'site_title' => $this->site_title,
        'site_uri' => $this->site_uri,
        'git_remote' => $this->git_remote,
        'state' => $this->state,
        'reason' => $this->reason,
        'data' => $this->data,
        'settings' => $this->settings,
    ];
    return $data;
  }

  public function toEntity() {
    $site = SiteEntity::create($this->toEntityData());
    return $site;
  }

  /**
   * Saves a site entity (or a new revision of the existing one.)
   * @return \Drupal\Core\Entity\ContentEntityBase|\Drupal\Core\Entity\EntityBase|\Drupal\Core\Entity\EntityInterface|SiteEntity
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveEntity($revision_log = '') {

    $site_entity = SiteEntity::load($this->site_uuid);
    if ($site_entity) {
      $site_entity->setNewRevision();
      $site_entity->revision_log = $revision_log;
      $site_entity_properties = $this->toEntityData();
      foreach ($site_entity_properties as $property => $value) {
        $site_entity->set($property, $value);
      }
    }
    else {
      $site_entity = $this->toEntity();
    }
    $site_entity->save();
    return $site_entity;
  }
}
