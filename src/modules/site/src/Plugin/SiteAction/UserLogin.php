<?php

namespace Drupal\site\Plugin\SiteAction;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\SiteActionPluginBase;
use Drupal\site\SiteSelf;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

/**
 * Plugin implementation of the site_action.
 *
 * @SiteAction(
 *   id = "user_login",
 *   label = @Translation("Log In"),
 *   description = @Translation("Retrieve a one-time-login link."),
 *   site_entity_operation = true,
 * )
 */
class UserLogin extends SiteActionPluginBase {

  use MessengerTrait;

  public function buildForm(array $form, FormStateInterface $form_state) {

    $current_site = SiteEntity::loadSelf();
    $current_user = User::load(\Drupal::currentUser()->id());

    $target_site = $form_state->get('plugin')->getSite();
    $target_site_url = $form_state->get('plugin')->getSite()->site_uri->value;

    if (\Drupal::currentUser()->hasPermission('bypass site action user login password requirement')) {
      $form['password'] = [
        '#type' => 'item',
        '#title' => t('Request a login link'),
        '#description' => t('Press the button below to request a one-time login link from @target_site.', [
          '@target_site' => Link::fromTextAndUrl($target_site_url, Url::fromUri($target_site_url))->toString(),
        ]),
      ];
    }
    else {
      // Ask for password for the site being used, unless user has 'bypass site action user login password requirement' permission.
      $form['password'] = [
        '#type' => 'password',
        '#title' => t('Current password'),
        '#description' => t('For security, enter your current password for @user at @site.', [
          '@user' => $current_user->toLink()->toString(),
          '@site' => $current_site->toLink()->toString(),
          '@target_site' => Link::fromTextAndUrl($target_site_url, Url::fromUri($target_site_url))->toString()
        ]),
        '#required' => TRUE,
      ];
    }

    $form['site_uuid'] = [
      '#type' => 'value',
      '#value' => $target_site->id(),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Request login link'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    // Allow special users to skip password check.
    if (!\Drupal::currentUser()->hasPermission('bypass site action user login password requirement')) {
      $password = trim($form_state->getValue('password'));
      $uid = \Drupal::service('user.auth')->authenticate(\Drupal::currentUser()->getAccountName(), $password);
      if (empty($uid)) {
        $form_state->setErrorByName('password', t('Incorrect password. Visit the @link page if needed.', [
          '@link' => Link::createFromRoute(t('Reset Password'), 'user.pass')->toString()
        ]));
      }
    }

    // Validate form: Retrieve remote entity, if not self.
    parent::validateForm($form, $form_state);

    // Set link from remote.
    if (!empty($form_state->get('action_api_return')['data']['links']['user_login']['href'])) {
      $link = $form_state->get('action_api_return')['data']['links']['user_login']['href'];
    }
    else {
      $link = $this->getLink();
    }

    if ($link) {
      $form_state->set('link', $link);
    }
    else {
      $form_state->setErrorByName('submit', t('Unable to retrieve link. An unknown error occurred.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if ($link = $form_state->get('link')) {
      $this->messenger()->addStatus(t('Your one-time login link has been generated. It will not be shown again, and can only be used once.'));
      $this->messenger()->addStatus(Link::fromTextAndUrl($link, Url::fromUri($link))->toString());
      $redirect = Url::fromRoute('entity.site.canonical', [
        'site' => $form_state->get('plugin')->getSite()->id(),
      ])->toString();
    }
  }

  /**
   * @inheritdoc
   */
  public function formPageTitle()
  {
    return t('Request login link from :site', [
      ':site' => $this->getSite()->label(),
    ]);
  }

  /**
   * Generate a one-time-login link.
   * @return \Drupal\Core\GeneratedUrl|string
   */
  public function getLink() {
      $timestamp = \Drupal::time()->getRequestTime();
      $account = User::load(\Drupal::currentUser()->id());

      $requested_from = \Drupal::request()->headers->get('requester') ??
        \Drupal::request()->getUri([
          'absolute' => TRUE,
        ]);

      $link = Url::fromRoute(
        'user.reset',
        [
          'uid' => $account->id(),
          'timestamp' => $timestamp,
          'hash' => user_pass_rehash($account, $timestamp),
        ],
        [
          'absolute' => true,
          'query' => [
            'requested-from' => $requested_from,
          ],
          'language' => \Drupal::languageManager()->getLanguage($account->getPreferredLangcode()),
        ]
      );
      return $link->toString();
  }

  /**
   * Only act on sites that have an api_url value.
   * @return bool|void
   */
  public function access()
  {
    // Only show this action if the site or project has an API key.
    $site = $this->getSite();
    if (method_exists($site, 'getApiKey') && ($this->getSite()->isSelf() || !empty($this->getSite()->getApiKey()))) {
      return parent::access();
    }
  }

  public function apiResponse(array &$response)
  {
    parent::apiResponse($response);
    $response['data']['links']['user_login']['href'] = $this->getLink();
  }
}
