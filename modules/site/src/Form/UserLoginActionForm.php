<?php

namespace Drupal\site\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\lazy_route_provider_install_test\PluginManager;
use Drupal\site\Entity\SiteDefinition;
use Drupal\site\Entity\SiteEntity;
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
  public function buildForm(array $form, FormStateInterface $form_state, $site = null) {

    $form['password'] = [
      '#type' => 'password',
      '#title' => t('Confirm Password'),
      '#description' => t('Enter your current password for site @site to retrieve a one-time login link.', [
        '@site' => Link::fromTextAndUrl(
          SiteEntity::getDefaultSiteTitle(),
          Url::fromUri(SiteEntity::getDefaultUri())
        )->toString(),
      ]),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Request Login Link'),
    ];

    $form['site_uuid'] = [
      '#type' => 'value',
      '#value' => $site->id(),
    ];

    // Site Manager: Alter form to post remotely.
    if (
      $site &&
      \Drupal::moduleHandler()->moduleExists('site_manager') &&
      !$site->isSelf() &&
      $site->isLatestRevision()
    ) {

      $t = [
        '@site' => Link::fromTextAndUrl(
          SiteEntity::getDefaultSiteTitle(),
          Url::fromUri(SiteEntity::getDefaultUri())
        )->toString(),
        '@target_site' => $site->toLink()->toString(),
        '@target_url' => $site->get('site_uri')->value,
      ];

      $form['actions']['submit']['#value'] = t('Request login link from @target_site', $t);
      $form['password']['#description'] = t('Enter your current password for this site (@site) to retrieve a one-time login link from remote site @target_site (@target_url).', $t);
    }

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

    // If not requesting from self, POST to get a link remotely
    $site = SiteEntity::load($form_state->getValue('site_uuid'));
    if ($site->isSelf()) {
      $link = $form_state->getValue('login_link');
      $this->messenger()->addStatus('Your one-time login link has been generated. It will not be shown again, and can only be used once.');
      $this->messenger()->addStatus($link);
    }

    // Requested password was not to self. Request via API.
    else {
      $this->messenger()->addStatus("@TODO: Attempt to get a link via the site's API.");
    }
  }
}
