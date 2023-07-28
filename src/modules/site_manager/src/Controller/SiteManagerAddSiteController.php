<?php

namespace Drupal\site_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\key_auth\KeyAuth;
use Drupal\user\Entity\User;

/**
 * Returns responses for Site Manager routes.
 */
class SiteManagerAddSiteController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['help'] = [
      '#type' => 'item',
      '#title' => t('Step 1'),
      '#markup' => $this->t('Install site.module in the website:'),
    ];

    $build['commands'] = [
      '#type' => 'html_tag',
      '#tag' => 'blockquote',
//      '#value' => 'composer require drupal/site-site',
    ];
    $build['commands']['composer'] = [
      '#type' => 'html_tag',
      '#tag' => 'pre',
      '#value' => 'composer require drupal/site-site:^1.10-alpha',
    ];
    $build['commands']['install'] = [
      '#type' => 'html_tag',
      '#tag' => 'pre',
      '#value' => 'drush en site',
    ];

    $account = \Drupal::currentUser()->getAccount();
    if (empty($account->api_key)) {
      \Drupal::messenger()->addWarning(t('You currently do not have an API key. @link', [
        '@link' => Link::createFromRoute('Create one', 'key_auth.user_key_auth_form', [
          'user' => $account->id(),
        ], [
          'query' => $this->getDestinationArray()
        ])->toString()
      ]));
    }
    else {
      \Drupal::messenger()->addStatus(t('Use the following instructions to connect a remote site.'));
    }
    $url = Url::fromRoute('rest.site_api.POST', [], [
      'absolute' => true,
      'query' => [
        'api-key' => $account->api_key ?? 'your-key-here',
      ]
    ])->toString();

    $build['configure'] = [
      '#type' => 'item',
      '#title' => t('Step 2'),
      '#markup' => $this->t('Configure site.module to send reports to this site:'),
    ];
    $build['steps'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ol',
      '#items' => [
        $this->t('Visit About this site > Settings page. (/admin/about/site)'),
        $this->t('Open the "Site Reporting" fieldset.'),
        $this->t('Check "Send on save".'),
        $this->t('Enter this URL into Site Data Destinations: <pre>@link</pre>', [
          '@link' => $url
        ]),
      ],
    ];
    return $build;
  }

}
