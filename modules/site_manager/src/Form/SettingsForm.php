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
    $form['global_config_overrides'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Global Site Config Overrides'),
      '#description' => $this->t('Enter YAML to load into all Site entities.'),
      '#default_value' => $this->config('site_manager.settings')->get('global_config_overrides'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    try {
      $settings = Yaml::parse($form_state->getValue('global_config_overrides'));
      if (is_string($settings) || !is_string(key($settings))) {
        $form_state->setErrorByName('global_config_overrides', $this->t('Global Site Config Overrides must be a yaml mapping. Use the format "name: value".'));
      }
    } catch (ParseException $e) {
      $form_state->setErrorByName('global_config_overrides', $this->t('Invalid Yaml: :message', [
        ':message' => $e->getMessage(),
      ]));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('site_manager.settings')
      ->set('global_config_overrides', $form_state->getValue('global_config_overrides'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
