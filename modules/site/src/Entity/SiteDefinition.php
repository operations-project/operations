<?php

namespace Drupal\site\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\site\SiteDefinitionInterface;
use Drupal\site\Event\SiteGetState;

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
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description"
 *   }
 * )
 */
class SiteDefinition extends ConfigEntityBase implements SiteDefinitionInterface {

  use StringTranslationTrait;

  /**
   * The site is not operating.
   */
  const SITE_ERROR = 2;

  /**
   * The site is operating normally.
   */
  const SITE_OK = 0;

  /**
   * The site is operating but with warnings.
   */
  const SITE_WARN = 1;

  /**
   * The site is operating and has information to present.
   */
  const SITE_INFO = -1;

  /**
   * Human readable strings for state.
   *
   * @var string
   */
  protected $stateNames = [
    self::SITE_OK => 'OK',
    self::SITE_INFO => 'OK (info)',
    self::SITE_WARN => 'Warning',
    self::SITE_ERROR => 'Error',
  ];

  /**
   * The site definition ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The site state: SITE_OK, SITE_WARN, SITE_ERROR
   *
   * @var int
   */
  protected int $state;

  /**
   * A string to describe the reason a site is in a certain state.
   *
   * @var string
   */
  protected string $reason;

  /**
   * The site definition label. Defaults to the site's title.
   *
   * @var string
   */
  protected string $label;

  /**
   * The site_definition description.
   *
   * @var string
   */
  protected string $description;

  /**
   * The site definition status.
   *
   * @var bool
   */
  protected $status;

  /**
   * A string representing the host provider of the site.
   * Loaded from DRUPAL_HOST if it exists.
   * @var string
   */
  protected string $host;

  /**
   * Sets label from site title
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->setDynamicProperties();
  }

  public function setDynamicProperties() {
    if ($this->isSelf()) {
      $this->label = \Drupal::config('system.site')->get('name');
      $this->host = getenv('DRUPAL_HOST') ?: 'unknown';
      $this->determineState();
    }
  }

  /**
   * Is this site definition for this site? Used for detectable fields.
   * @return bool
   */
  public function isSelf() {
    return $this->id == 'self';
  }

  /**
   * Return a build array with a nice summary of the site.
   * @return array[]
   */
  public function view() {
    $strings = [
      ':label' => $this->label(),
      ':description' => $this->description,
      ':host' => $this->host,
      ':state' => $this->getStateName(),
      '@reason' => check_markup($this->reason),
    ];
    return [
      'info' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t("State: :state", $strings),
          $this->t("Reason: @reason", $strings),
          $this->t("Title: :label", $strings),
          $this->t("Description: :description", $strings),
          $this->t("Host: :host", $strings),
        ]
      ]
    ];
  }

  /**
   * @return int
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
   * @return int
   */
  public function getStateName() {
    return $this->stateNames[$this->state];
  }
}
