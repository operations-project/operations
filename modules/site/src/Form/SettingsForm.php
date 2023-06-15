<?php

namespace Drupal\site\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Configure Site settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'operations_site_settings';
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

    $form['site_state'] = [
        '#type' => 'fieldgroup',
        '#title' => $this->t('Site State'),
    ];
    $form['site_state']['status_report_state'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use <a href=":url">Status Report</a> to determine site state.', [
          ':url' => '/admin/reports/status',
      ]),
      '#default_value' => $this->config('site.settings')->get('status_report_state'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('site.settings')
      ->set('status_report_state', $form_state->getValue('status_report_state'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
