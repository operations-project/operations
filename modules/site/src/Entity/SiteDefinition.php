<?php

namespace Drupal\site\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
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
 *     "configs_remote",
 *     "data"
 *   }
 * )
 */
class SiteDefinition extends ConfigEntityBase implements SiteDefinitionInterface, ConfigEntityInterface {

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
   * Sets label from site title
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->setDynamicProperties();
    $this->setConfig();
  }

  public function setDynamicProperties() {
    if ($this->isSelf()) {
      $this->site_title = \Drupal::config('system.site')->get('name');
      $this->site_uuid = \Drupal::config('system.site')->get('uuid');
      $this->site_uri = \Drupal::request()->getSchemeAndHttpHost();

      $this->determineState();
    }
  }

  public function setConfig() {
    if (isset($this->configs_load)) {
      foreach ($this->configs_load as $config_id) {
        $config_id = trim($config_id);
        $this->config[$config_id] = \Drupal::config($config_id)->get();
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
    $strings = [
      ':label' => $this->site_title,
      ':git_remote' => $this->git_remote,
      ':description' => $this->description,
      ':state' => $this->stateName(),
      '@reason' => check_markup($this->reason),
    ];
    $build = [
      'info' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t("Title: :label", $strings),
          $this->t("Git Remote: :git_remote", $strings),
          $this->t("State: :state", $strings),
          $this->t("Reason: @reason", $strings),
          $this->t("Description: :description", $strings),
        ]
      ]
    ];

    if (!empty($this->config)) {
      $build['config'] = [
        '#type' => 'details',
        '#title' => t('Site Configuration'),
      ];
      foreach ($this->config as $config => $data) {
        $build['config'][$config] = [
          '#type' => 'item',
          '#title' => $config,
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
}
