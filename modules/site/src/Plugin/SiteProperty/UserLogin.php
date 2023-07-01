<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\site\Form\UserLoginActionForm;
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
  public function view() {
    $build = [
      '#type' => 'item',
      '#title' => $this->label(),
      '#description' => t('Press this button to get a one-time login link.'),
      '#description_display' => 'after',
      'request' => \Drupal::formBuilder()->getForm(UserLoginActionForm::class),
    ];
    return $build;
  }

  /**
   * @return mixed
   * @TODO: This might get saved in the config! How can we make sure it doesn't?
   */
  public function value() {

    $content = \Drupal::request()->getContent();
    $name = \Drupal::request()->getContent(); // form param

    // If request is POST, action is user-login, and user is authenticated, return a login link.
    if (\Drupal::request()->getMethod() == 'POST'
//      && \Drupal::request()->get('action') == 'user-login'
      && \Drupal::currentUser()->isAuthenticated()
      && \Drupal::currentUser()->hasPermission('request login link')
    ) {
      $timestamp = \Drupal::time()->getRequestTime();
      $account = User::load(\Drupal::currentUser()->id());
      $link = Url::fromRoute(
        'user.reset.login',
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
      return $link->toString();
    }
    return '';
  }

  /**
   * Define a
   *
   * @return static
   *   A new field definition object.
   */
  public function baseFieldDefinitions(EntityTypeInterface $entity_type, &$fields) {
    $fields[$this->name()] = BaseFieldDefinition::create('string')
      ->setLabel($this->label())
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);
  }
}
