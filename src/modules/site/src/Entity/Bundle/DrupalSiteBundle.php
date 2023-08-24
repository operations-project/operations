<?php

namespace Drupal\site\Entity\Bundle;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Link;
use Drupal\site\Entity\DrupalProject;
use Drupal\site\Entity\SiteEntity;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

/**
 * A bundle class for site entities.
 */
class DrupalSiteBundle extends PhpSiteBundle {

  public function save()
  {
    // If no api_url, set from URI.
    if (empty($this->api_url->getValue())) {
      $url = parse_url($this->site_uri->value);
      $url_host = $url['host'];
      $this->api_url->setValue($url['scheme'] . '://' . $url['host']);
    }
    parent::save();
  }

  /**
   * Add a check for Drupal Site API.
   * @return void
   */
  public function getRemote() {
    if (empty($this->api_url->value)) {
      return;
    }

    // Load Drupal Site API info.
    $api_url = $this->api_url->value;
    $api_url .= "/jsonapi/self";
    $api_key = $this->api_key->value ?? '';

    $payload['request'] = 'site';
    $options = [
      'headers' => [
        'Accept' => 'application/json',
        'api-key' => $api_key,
      ],
      'json' => $payload,
    ];
    try {
      $response = \Drupal::httpClient()->get($api_url, $options);
      $received_content = $response->getBody()->getContents();
      $response_data = Json::decode($received_content);
      foreach ($this->getFields() as $field_id => $field) {
        if (!empty($response_data['data']['attributes'][$field_id])) {

          // If JSONAPI worked, this wouldn't be needed.
          switch ($field_id) {
            case 'revision_timestamp':
            case 'revision_log':
              continue(2);

            case 'reason':
              $value = $response_data['data']['attributes'][$field_id];
              break;

            case 'created':
            case 'changed':
              $value = strtotime($response_data['data']['attributes'][$field_id]);
              break;
            default:
              $value = $response_data['data']['attributes'][$field_id];

          }
          $this->set($field_id, $value);
        }
      }
      $this->setRevisionLogMessage(t('Retrieved data from :url'));

    } catch (ClientException $e) {

      $received_content = $e->getResponse()->getBody()->getContents();
      $response_data = Json::decode($received_content);


      // This checks any drupal Site type for a Site API.
      // This doesn't necessarily warrant an error.
      switch($e->getCode()) {
        case 404:
          $reason = [
            '#type' => 'item',
            '#title' => t('Site API not found.'),
            '#markup' => t('Install Site Module for enhanced functionality: <code>composer require drupal/site</code>. See @link for more information. The requested API URL was @api', [
              '@link' => Link::fromTextAndUrl(t('drupal.org/project/site'), \Drupal\Core\Url::fromUri('https://www.drupal.org/project/site'))->toString(),
              '@api' => Link::fromTextAndUrl($api_url, \Drupal\Core\Url::fromUri($api_url))->toString(),
            ])
          ];
          break;
        case 403:
          $reason = [
            '#type' => 'item',
            '#title' => t('Site API Access Denied.'),
            '#markup' => t('Check your API key and try again.'),
          ];
          $this->state = SiteEntity::SITE_ERROR;
          break;
      }
    }
    catch (\Exception $e) {
      $reason = [
        '#markup' => t('Something went wrong when saving data from @api: %error', [
          '%error' => $e->getMessage(),
          '@api' => Link::fromTextAndUrl($api_url, \Drupal\Core\Url::fromUri($api_url))->toString(),
        ])
      ];
      $this->state = SiteEntity::SITE_ERROR;
    }

    // @TODO: Running parent::getRemote() obliterates the received reasons data.

    parent::getRemote();

    // Append this reason.
    if (!empty($reason)) {
      $reasons = $this->get('reason')->get(0)->getValue();
      $reasons[] = $reason;
      $this->get('reason')->set(0, $reasons);
    }

    // Post process properties.
    // @TODO: Move this to SiteProperty plugins
    // @TODO: Zero out properties so that old revision fields don't appear here.
    // Otherwise this will never change once it is in the database.
    if (empty($this->php_version->value) && $this->headers->get('x-powered-by') && strpos($this->headers->get('x-powered-by'), 'PHP') === 0) {
      $this->set('php_version', $this->headers->get('x-powered-by'));
    }
    if (empty($this->drupal_version->value) && $this->headers->get('x-generator') && strpos($this->headers->get('x-generator'), 'Drupal') === 0) {
      $this->set('drupal_version', $this->headers->get('x-generator'));
    }

    return $this;
  }
}
