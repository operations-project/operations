<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\Form\UserLoginActionForm;
use Drupal\site\SiteInterface;
use Drupal\site\SitePropertyPluginBase;
use Drupal\user\Entity\User;
use Drush\Commands\core\LoginCommands;

/**
 * Provide a valid user login link.
 *
 * @SiteProperty(
 *   id = "user_login",
 *   name = "user_login",
 *   label = @Translation("Login Link"),
 *   description = @Translation("A one-time user login link."),
 *   hidden = false
 * )
 */
class UserLogin extends SitePropertyPluginBase {

  /**
   * @return array
   */
  public function view(SiteEntity $site = null) {
    $build['login'] = [
      '#title' => t('Get Login Link'),
      '#type' => 'details'
    ];
    $build['login']['form'] = \Drupal::formBuilder()->getForm(UserLoginActionForm::class, $site);

    return $build;
  }


  /**
   * @return array
   */
  public function entityView(SiteEntity $site) {

    // There's no reason to show this on an entity view.
    // All interaction with self would be done on the main Site status page.
    // This is only used with Site Manager
    $build = $this->view($site);
    return $build;
  }

  /**
   * @return mixed
   * @TODO: This might get saved in the config! How can we make sure it doesn't?
   */
  public function value($show_login = false) {
    $show_login = true;
    $return = false;
    $content = \Drupal::request()->getContent();
    $name = \Drupal::request()->getContent(); // form param
    $hide_login = false;
    // If request is POST, action is user-login, and user is authenticated, return a login link.
    if ($show_login
      && \Drupal::request()->getMethod() == 'POST'
//      && \Drupal::request()->get('action') == 'user-login'
      && \Drupal::currentUser()->isAuthenticated()
      && \Drupal::currentUser()->hasPermission('request login link')
    ) {
      $timestamp = \Drupal::time()->getRequestTime();
      $account = User::load(\Drupal::currentUser()->id());
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
            'requested-from' => \Drupal::request()->getRequestUri([
              'absolute' => TRUE,
            ]),
          ],
          'language' => \Drupal::languageManager()->getLanguage($account->getPreferredLangcode()),
        ]
      );
      $return = $link->toString();
    }
    return $return;
  }
}
