<?php

namespace Drupal\site\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\SiteEntityInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a Site form.
 */
class SiteActionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_action';
  }

  /**
   * @return string
   */
  public function formPageTitle(SiteEntityInterface $site, string $plugin_id) {
    try {
      $plugin = \Drupal::service('plugin.manager.site_action')->createInstance($plugin_id, [
        'site' => $site,
      ]);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException();
    }
    return $plugin->formPageTitle();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $plugin = $form_state->get('plugin');
    return $plugin->buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $plugin = $form_state->get('plugin');
    $plugin->validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $plugin = $form_state->get('plugin');
    $plugin->submitForm($form, $form_state);
  }
}
