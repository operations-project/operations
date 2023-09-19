<?php

namespace Drupal\site;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\migrate\Plugin\Exception\BadPluginDefinitionException;
use Drupal\site\Annotation\SiteProperty;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\SitePropertyPluginManager;
use function PHPUnit\Framework\stringContains;

/**
 * A service to retrieve information about this site.
 */
class SiteSelf {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The plugin.manager.site_property service.
   *
   * @var \Drupal\site\SitePropertyPluginManager
   */
  protected $siteProperty;

  /**
   * @var int The overall state of the site.
   */
  protected $state;

  /**
   * @var array A build array of information to show to users.
   */
  protected $reasons;

  /**
   * @var array A build array of information to save to data field..
   */
  protected $data;

  /**
   * @var array An array of property plugins.
   */
  protected $property_plugins;

  /**
   * @var array An array of property values.
   */
  protected $properties = [];

  /**
   * @var SiteEntity The site entity for this site.
   */
  protected $entity;

  /**
   * Constructs a SiteSelf object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\site\SitePropertyPluginManager $site_property
   *   The plugin.manager.site_property service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger, SitePropertyPluginManager $site_property) {
    $this->configFactory = $config_factory;
    $this->logger = $logger;
    $this->siteProperty = $site_property;
  }

  /**
   * Get a site entity for the current site.
   * If one does not exist, return an un-saved site entity.
   *
   * @return SiteEntity
   */
  public function getEntity() {
    return $this->entity ?? SiteEntity::loadSelf() ?? $this->createEntity();
  }

  /**
   * Set a site entity for the current site.
   *
   * @return SiteSelf
   */
  public function setEntity($entity) {
    $this->entity = $entity;
    return $this;
  }

  /**
   * Set Properties, state and reason.
   * @param $entity
   * @return void
   */
  public function prepareEntity() {
    if (empty($this->entity)) {
      $this->entity = $this->getEntity();
    }

    /** @var SiteSelf $site_service */
    $this->load();

    // Set state and reason.
    $this->entity->set('state', $this->getState());
    $this->entity->set('reason', $this->getReasons());
    $this->entity->set('data', $this->getData());

    // Set all properties from plugins.
    foreach ($this->getProperties() as $name => $value) {
      if ($this->entity->hasField($name)) {
        $this->entity->set($name, $value);
      }
    }
    return $this;
  }

  /**
   * @return int
   */
  public function getState() {
    return $this->state;
  }

  /**
   * @return array
   */
  public function getReasons() {
    return $this->reasons;
  }
  /**
   * @return array
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Return a property plugin.
   * @return SiteProperty
   * @usage \Drupal::service('site')->get('site_url')->value();
   * @usage \Drupal::service('site')->get('site_uuid')->value();
   * @usage \Drupal::service('site')->get('git_url')->value();
   */
  public function getProperty($property_plugin_id) {
    return $this->siteProperty->createInstance($property_plugin_id);
  }

  /**
   * Load all currently loaded property values.
   * @return array
   */
  public function getProperties() {
    if (empty($this->properties)) {
      $this->load();
    }
    return $this->properties;
  }
  /**
   * @return array
   */
  public function load() {
    $worst_plugin_state = SiteInterface::SITE_OK;
    $plugin_definitions = $this->siteProperty->getDefinitions();
    foreach ($plugin_definitions as $name => $plugin_definition) {
      $plugin = $this->getProperty($plugin_definition['id']);

      // Detect bundle specific plugin, and bail if not for this bundle.
      if (!empty($plugin_definition['site_bundles'])) {
        foreach ($plugin_definition['site_bundles'] as $plugin_site_bundle_class) {
          if ($this->entity) {
            $entity_class = get_class($this->entity);
            if ($entity_class == $plugin_site_bundle_class || is_subclass_of($entity_class, $plugin_site_bundle_class)) {

              // Load property object into $this->property_plugins;
              $this->property_plugins[$name] = $plugin;
              $property_name = $plugin->name();
              $property_value = $plugin->value();
              $property_state = $plugin->state();
              $property_reasons = $plugin->reason();
              $property_data = $plugin->siteData();

              // Load property name and value.
              $this->properties[$property_name] = $property_value;
              $this->reasons[$property_name] = $property_reasons;
              $this->data[$property_name] = $property_data;

              // Set worst state.
              if ($property_state > $worst_plugin_state) {
                $worst_plugin_state = $property_state;
              }
            }
          }
        }
      }
    }
    $this->state = $worst_plugin_state;
    return $this;
  }

  /**
   * Creates a new SiteEntity with all properties set.
   * @return SiteEntity
   */
  public function createEntity($values = []) {

    // Load property and state data.
    $this->load();

    $values += $this->properties;
    $values['state'] = $this->state;
    $values['reason'] = $this->reasons;
    $values['data'] = $this->data;

    // This is coming from Drupal site.self service, so it's a "drupal" site.
    $values['site_type'] = 'drupal';

    $values['site_uri'] = SiteEntity::getDefaultUri();
    $values['hostname'] = SiteEntity::getDefaultHostname();

    return SiteEntity::create($values);
  }

  /**
   * @param $log_message
   * @return SiteEntity
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveEntity($log_message = '') {
    $entity = $this->getEntity();

    // Prepare entity needs to be called before save so that the properties are included in validate.
    $this->prepareEntity($entity);

    $entity
      ->skipPrepare()
      ->setRevisionLogMessage($log_message)
      ->setValidationRequired(true);
    $violations = $entity->validate();
    if (count($violations)) {
      throw new EntityStorageException($violations);
    }
    $entity->save();
    return $entity;
  }

  public function sendEntity() {

  }

  /**
   * Return a build array with a nice summary of the site.
   * @return array[]
   */
  public function view() {
    $build = [];
    $site_entity = $this->getEntity();

    // Don't show live properties on view() pages.
    // $this->prepareEntity($site_entity);

    $build['site'] = $site_entity->view();
    return $build;
  }
}
