<?php

namespace Drupal\site;

use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\ContextAwarePluginBase;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Plugin\Context\LazyContextRepository;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\site\Entity\SiteEntity;

/**
 * Base class for site_property plugins.
 */
abstract class SitePropertyPluginBase extends ContextAwarePluginBase implements SitePropertyInterface {

  use ContextAwarePluginTrait;
  use ContextAwarePluginAssignmentTrait;

  /**
   * @var mixed The name of this property.
   */
  protected $name;

  /**
   * @var mixed The value of this property.
   */
  protected $value;

  /**
   * @var mixed A description of the property.
   */
  protected $description;

  /**
   * @var bool Hide the property on view pages.
   */
  protected $hidden;

  /**
   * @var array A build array of information to display to users in the site report view.
   */
  protected $reason;

  /**
   * @var array A build array of information to storein the data field.
   */
  public $siteData;

  /**
   * @var SiteEntity The site entity this property is derived from.
   */
  protected $site;

  /**
   * @var int The state this property generated.
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LazyContextRepository $contextRepository) {
    $this->contextRepository = $contextRepository;

    // Pass the other parameters up to the parent constructor.
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // set the defined contexts' values
    $this->setDefinedContextValues();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('context.repository')
    );
  }
  /**
   * Set values for the defined contexts of this plugin
   *
   */

  private function setDefinedContextValues() {
    // fetch the available contexts
    $available_contexts = $this->contextRepository->getAvailableContexts();

    // ensure that the contexts have data by getting corresponding runtime contexts
    $available_runtime_contexts = $this->contextRepository->getRuntimeContexts(array_keys($available_contexts));
    $plugin_context_definitions = $this->getContextDefinitions();
    foreach ($plugin_context_definitions as $name => $plugin_context_definition) {

      // identify and fetch the matching runtime context, with the plugin's context definition
      $matches = $this->contextHandler()
        ->getMatchingContexts($available_runtime_contexts, $plugin_context_definition);
      $matching_context = reset($matches);

      // set the value to the plugin's context, from runtime context value
      if ($matching_context) {
        $this->setContextValue($name,$matching_context->getContextValue());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function name() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function hidden() {
    return $this->pluginDefinition['hidden'] ?? false;
  }

  /**
   * {@inheritdoc}
   */
  public function value() {
    return $this->value ?? $this->pluginDefinition['default_value'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function reason() {
    return $this->reason;
  }

  /**
   * {@inheritdoc}
   */
  public function siteData() {
    return $this->siteData;
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->description ?? $this->pluginDefinition['description'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function site() {
    return $this->site;
  }

  /**
   * {@inheritdoc}
   */
  public function state() {
    return $this->site;
  }

  /**
   * Return a build array on site definition view pages.
   * @return array
   */
  public function view() {
    return [
      '#type' => 'item',
      '#title' => $this->label(),
      '#description' => $this->description(),
      '#description_display' => 'after',
      '#markup' => $this->value(),
    ];
  }

  /**
   * Return a build array to show in the Site Entity view, if neeeded.
   * @return array
   */
  public function entityView(SiteEntity $site) {
    return [];
  }

  /**
   * Define a field on Siteentity
   *
   * @return static
   *   A new field definition object.
   *
   * The SiteDefinition::saveEntity class checks for the existi
   */
//  public function baseFieldDefinitions(EntityTypeInterface $entity_type, &$fields) {
    // Add additional fields, just like in SiteEntity::baseFieldDefinitions()
//    $fields['drupal_version'] = BaseFieldDefinition::create('string')
//      ->setLabel(t('Drupal Version'))
//      ->setRevisionable(TRUE)
//      ->setDisplayOptions('view', [
//        'type' => 'string',
//        'label' => 'inline',
//        'weight' => 10,
//      ])
//      ->setDisplayConfigurable('view', TRUE);
//  }

  /**
   * Add item to the state reason build array.
   * @param array $build
   * @return void
   */
  public function addReason(array $build) {
    $this->reason[] = $build;
  }

  /**
   * Add item to the data build.
   * @param array $build
   * @return void
   */
  public function addData($key, $value) {
    $this->siteData[$key] = $value;
  }
  /**
   * Ensures the t() method is available.
   *
   * @see \Drupal\Core\StringTranslation\StringTranslationTrait
   */
  protected function t($string, array $args = [], array $options = []) {
    return parent::t($string, $args, $options);
  }

}
