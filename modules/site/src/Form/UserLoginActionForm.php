<?php

namespace Drupal\site\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\lazy_route_provider_install_test\PluginManager;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Site form.
 */
class UserLoginActionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_user_login_action';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['password'] = [
      '#type' => 'password',
      '#title' => t('Enter password'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Request Login Link'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $password = trim($form_state->getValue('password'));
    $uid = \Drupal::service('user.auth')->authenticate(\Drupal::currentUser()->getAccountName(), $password);
    if (empty($uid)) {
      $form_state->setErrorByName('password', $this->t('Incorrect password. You can receive a link via email on the @link page.', [
        '@link' => Link::createFromRoute($this->t('Reset Password'), 'user.pass')->toString()
      ]));
    }
    else {
      $type = \Drupal::service('plugin.manager.site_property');
      $plugin = $type->createInstance('user_login');
      $link = $plugin->value();
      if ($link) {
        $form_state->setValue('login_link', $link);
      }
      else {
        $form_state->setErrorByName('submit', $this->t('Something went wrong. The login link was not generated.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $link = $form_state->getValue('login_link');
    $this->messenger()->addStatus('Your one-time login link has been generated. It will not be shown again, and can only be used once.');
    $this->messenger()->addStatus($link);
  }
}
