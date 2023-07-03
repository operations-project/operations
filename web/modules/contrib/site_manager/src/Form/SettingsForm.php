<?php

namespace Drupal\site_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Configure Site Manager settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_manager_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['site_manager.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['allowed_remote_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List of fields that client site POSTs are allowed to overwrite in this site.'),
      '#description' => $this->t("When client sites post records here, allow these fields to be set in the site entity here."),
      '#default_value' => $this->config('site_manager.settings')->get('allowed_remote_fields'),
    ];
    $form['global_config_overrides'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Global Site Config Overrides'),
      '#description' => $this->t("Enter YAML to load into all Site entities' 'config_overrides' property."),
      '#default_value' => $this->config('site_manager.settings')->get('global_config_overrides'),
    ];
    $form['global_state_overrides'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Global Site State Overrides'),
      '#description' => $this->t("Enter YAML to load into all Site entities' 'state_overrides' property"),
      '#default_value' => $this->config('site_manager.settings')->get('global_state_overrides'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $global_config_items = [
      'config_overrides',
      'state_overrides',
    ];
    foreach ($global_config_items as $config_name) {
      try {
        $settings = Yaml::parse($form_state->getValue('global_' . $config_name));
        if (is_string($settings) || (is_array($settings) && !is_string(key($settings)))) {
          $form_state->setErrorByName('global_' . $config_name, $this->t('Global Site Overrides must be a yaml mapping. Use the format "name: value".'));
        }
      } catch (ParseException $e) {
        $form_state->setErrorByName('global_' . $config_name, $this->t('Invalid Yaml: :message', [
          ':message' => $e->getMessage(),
        ]));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('site_manager.settings')
      ->set('global_config_overrides', $form_state->getValue('global_config_overrides'))
      ->set('global_state_overrides', $form_state->getValue('global_state_overrides'))
      ->set('allowed_remote_fields', $form_state->getValue('allowed_remote_fields'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
