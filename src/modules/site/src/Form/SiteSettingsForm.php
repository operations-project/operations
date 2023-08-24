<?php

namespace Drupal\site\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\jsonapi\Access\EntityAccessChecker;
use Drupal\jsonapi\Controller\EntityResource;
use Drupal\jsonapi\Encoder\JsonEncoder;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\Normalizer\ContentEntityDenormalizer;
use Drupal\jsonapi\Normalizer\ResourceObjectNormalizer;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi\Serializer\Serializer;
use Drupal\site\Entity\SiteEntity;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * Configuration form for Site entity module..
 */
class SiteSettingsForm extends ConfigFormBase {


  protected array $array_fields = [
      'fields_allow_override' ,
      'configs_load' ,
      'configs_allow_override',
      'states_load',
      'states_allow_override'
    ];

  /**
   * @inheritdoc
   */
  protected function config($name = 'site.settings') {
    return parent::configFactory()->getEditable($name);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_entity_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['site.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $intervals = [60, 900, 1800, 3600, 7200, 10800, 21600, 32400, 43200, 64800, 86400, 172800, 259200, 604800, 1209600, 2419200];
    $period = array_map([\Drupal::service('date.formatter'), 'formatInterval'], array_combine($intervals, $intervals));
    $options = [0 => t('Never')] + $period;

    $form['information'] = array(
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-info',
      '#weight' => 10,
    );
    $form['settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#group' => 'information',
    );
    $form['state'] = array(
      '#type' => 'details',
      '#title' => $this->t('Site State'),
      '#group' => 'information',
    );

    $form['data'] = array(
      '#type' => 'details',
      '#title' => $this->t('Report Data'),
      '#group' => 'information',
    );
    $form['site_manager'] = array(
      '#type' => 'details',
      '#title' => $this->t('Site Manager Connection'),
      '#group' => 'information',
      '#tree' => true,
      '#description' => t('This site can connect to Site Manager instances for tracking and control. Enter details below or @link.', [
        '@link' => Link::createFromRoute(t('Add a Site Manager'), 'entity.site.add_form', [
          'site_type' => 'site_manager',
        ])->toString()
      ]),
    );

    $form['site_manager']['send_on_save'] = [
      '#type' => 'checkbox',
      '#title' => t('Send reports when saved'),
      '#description' => t('Send a site report every time one is saved from this site.'),
      '#default_value' => $this->config()->get('site_manager.send_on_save'),
    ];

    $site_manager_settings = $this->configFactory()->get('site.settings')->get('site_manager');
    $suggested_manager = $site_manager_settings['suggested_api_name'] ?? '';
    $suggested_manager_url = $site_manager_settings['suggested_api_url'] ?? '';

    if ($suggested_manager_url && $suggested_manager) {
      $api_description = t('Enter the URL of a Site Manager instance to connect to, such as @link', [
        '@link' => Link::fromTextAndUrl($suggested_manager, Url::fromUri($suggested_manager_url))->toString(),
      ]);
    }
    else {
      $api_description = t('Enter the URL of a Site Manager instance to connect to.');
    }
    $form['site_manager']['api_url'] = [
      '#type' => 'url',
      '#title' => t('Site Manager API URL'),
      '#description' => $api_description,
      '#default_value' => $this->config()->get('site_manager.api_url'),
    ];
    $form['site_manager']['api_key'] = [
      '#type' => 'password',
      '#title' => t('Site Manager API Key'),
      '#description' => t('Enter the API Key from the Site Manager instance. To get a key, in Site Manager, visit <em>My Account > Key Authentication</em>.'),
      '#default_value' => $this->config()->get('site_manager.api_key'),
    ];

//    $form['site_manager']['send_destinations'] = [
//      '#title' => $this->t('Additional Destinations'),
//      '#description' => $this->t('Enter the URLs to POST site data to, one per line. To connect to a Site Manager instance, use the path "https://site_manager_url/api/site/data?api-key=xyz".'),
//      '#default_value' => $this->config()->get('site_manager.send_destinations'),
//      '#type' => 'textarea',
//      '#states' => [
//        'invisible' => [
//          ':input[name="settings[site_entity][send_interval]"]' => ['value' => 0]
//        ]
//      ],
//    ];
    $form['site_manager']['fields_allow_override'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Remote Field Overrides'),
      '#default_value' => $this->config()->get('site_manager.fields_allow_override'),
      '#description' => $this->t('The fields that can be controlled by Site Manager, one per line.'),
    ];
    $form['data']['configs_load'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Stored Configuration items'),
      '#default_value' => $this->config()->get('configs_load'),
      '#description' => $this->t('A list of configuration items that should be saved in site reports. Use the main config key, or get a specific item by separating with a color. For example: <pre>system.site</pre> or <pre>core.extension:theme</pre>.'),
    ];
    $form['site_manager']['configs_allow_override'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Remote Config Overrides'),
      '#default_value' => $this->config()->get('site_manager.configs_allow_override'),
      '#description' => $this->t('Configuration items that can be controlled by Site Manager, one per line.'),
    ];
    $form['data']['states_load'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Stored State items.'),
      '#default_value' => $this->config()->get('states_load'),
      '#description' => $this->t('A list of state items that should be saved in site reports.'),
    ];
    $form['site_manager']['states_allow_override'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Remote State Overrides'),
      '#default_value' => $this->config()->get('site_manager.states_allow_override'),
      '#description' => $this->t('State items that can be controlled by Site Manager, one per line.'),
    ];
    $form['state']['state_factors'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('State Factors'),
      '#description' => $this->t('Choose the factors that will affect site state.'),
      '#options' => [
        'system' => $this->t('Status Report <a href=":url">view</a>', [
          ':url' => '/admin/reports/status',
        ])
      ],
      '#default_value' => $this->config()->get('state_factors') ?? [],
    ];
    $form['settings']['save_on_config'] = [
      '#type' => 'checkbox',
      '#title' => t('Save site report on config changes'),
      '#description' => t('If checked, site reports are saved whenever configuration changes.'),
      '#default_value' => $this->config()->get('save_on_config'),
    ];
    $form['settings']['save_interval'] = [
      '#type' => 'select',
      '#title' => t('Save site report every'),
      '#description' => t('Regularly save site data for later review.'),
      '#default_value' =>  $this->config()->get('save_interval'),
      '#options' => $options,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);

    // Don't erase api key.
    $site_manager_form_values = $form_state->getValue('site_manager');
    if (empty($site_manager_form_values['api_key'])) {
      $site_manager_form_values['api_key'] = $this->configFactory()->get('site.settings')->get('site_manager')['api_key'];
      $form_state->setValue('site_manager', $site_manager_form_values);
    }

    // Test connection.
    // Create a fake site_manager entity and run validate();

    if (!empty($form_state->getValue('site_manager')['api_url'])) {
      $site_manager = SiteEntity::create([
        'site_type' => 'site_manager',
        'site_uri' => $form_state->getValue('site_manager')['api_url'],
        'hostname' => $form_state->getValue('site_manager')['api_url'],
        'api_key' => $form_state->getValue('site_manager')['api_key'],
      ]);

      $violations = $site_manager->validate();

      if (count($violations)) {
        $messages = [];
        foreach ($violations as $violation){
          $messages[] = $violation->getMessage();
        }
        $message = [
          '#theme' => 'item_list',
          '#items' => $messages,
        ];
        $form_state->setErrorByName('site_manager][api_url',\Drupal::service('renderer')->render($message));
      }
      else {
        \Drupal::messenger()->addStatus(t('Successfully connected to Site Manager @link!', [
          '@link' => Link::fromTextAndUrl($form_state->getValue('site_manager')['api_url'], Url::fromUri($form_state->getValue('site_manager')['api_url']))->toString(),
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('site.settings')
      ->set('state_factors', $form_state->getValue('state_factors'))
      ->set('configs_load', $form_state->getValue('configs_load'))
      ->set('states_load', $form_state->getValue('states_load'))
      ->set('settings', $form_state->getValue('settings'))
      ->set('save_on_config', $form_state->getValue('save_on_config'))
      ->set('save_interval', $form_state->getValue('save_interval'))
      ->set('site_manager', $form_state->getValue('site_manager'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
