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
   * Determine if this site is the canonical site for this project.
   *
   * @param string $url If specified, will only check for the specific url instead of .
   * @return bool
   */
  public function isCanonical($url = null)
  {
    $canonical_url = $this->drupal_project->entity->canonical_url->value ?? null;
    if (empty($canonical_url)) {
      return false;
    }

    if ($url) {
      return $canonical_url == $url;
    }

    foreach ($this->site_uri->getValue() as $site_uri) {
      if ($site_uri['value'] == $canonical_url) {
        return true;
      }
    }
    return false;
  }

  /**
   * Add a check for Drupal Site API.
   * @return void
   */
  public function getRemote() {
    if (empty($this->api_url->value) || empty($this->getApiKey())) {
      if ($this->get('drupal_project')->first()) {
        $project = $this->drupalProject();
        $message = t('Add an API Key to this <a href=":site_url">site</a> or <a href=":project_url">project</a> for enhanced functionality.', [
          ':site_url' => $this->isNew()? '#': $this->toUrl('edit-form', ['absolute' => true])->toString(),
          ':project_url' => $project->isNew()? '#': $project->toUrl('edit-form', ['absolute' => true])->toString(),
        ]);
      }
      else {
        $message = t('Add an API Key to this <a href=":site_url">site</a> for enhanced functionality.', [
          ':site_url' => $this->isNew()? '#': $this->toUrl('edit-form', ['absolute' => true])->toString(),
        ]);
      }
      $this->addReason([
        '#type' => 'item',
        '#title' => t('Site API credentials not found in Site Manager.'),
        '#markup' => $message,
      ], 'site_api_no_creds');

      // Everything else in this function uses site API. Don't bother without creds.
      $state = SiteEntity::SITE_WARN;

      // Set state.
      if ($this->state->value < $state) {
        $this->state->setValue($state);
      }

      parent::getRemote();
      return;
    }

    // Load Drupal Site API info.
    $api_url = $this->api_url->value ?? $this->site_uri->value;
    $api_url .= "/jsonapi/self";
    $api_key = $this->getApiKey();

    $state = SiteEntity::SITE_OK;

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

      // If this entity is being saved from a JSONAPI POST/PATCH, don't
      // save fields again, because we just received remote data.
      // Run the SiteBundle::retRemote() only.
      if (\Drupal::request()->getMethod() == 'PATCH' || \Drupal::request()->getMethod() == 'POST') {
        return parent::getRemote();
      }

      $received_content = $response->getBody()->getContents();
      $response_data = Json::decode($received_content);
      foreach ($this->getFields() as $field_id => $field) {
        if (!empty($response_data['data']['attributes'][$field_id])) {

          // @TODO: This is a copy of code in SiteEntity::send(). Create a method for this.
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

            // @TODO: ?
            case 'drupal_cron_last':
            case 'drupal_install_time':
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
          $this->addReason([
            '#type' => 'item',
            '#title' => t('Site API not found.'),
            '#markup' => t('Install Site Module for enhanced functionality: <code>composer require drupal/site</code>. See @link for more information. The requested API URL was @api', [
              '@link' => Link::fromTextAndUrl(t('drupal.org/project/site'), \Drupal\Core\Url::fromUri('https://www.drupal.org/project/site'))->toString(),
              '@api' => Link::fromTextAndUrl($api_url, \Drupal\Core\Url::fromUri($api_url))->toString(),
            ])
          ], 'site_api_404');
          break;
        case 403:
          if ($this->get('drupal_project')->first()) {
            $project = $this->drupalProject();
            $message = t('Site API was found, but the request was denied. Check the API Key in the <a href=":site_url">site</a> or <a href=":project_url">project</a>.', [
              ':site_url' => $this->isNew()? '#': $this->toUrl('edit-form', ['absolute' => true])->toString(),
              ':project_url' => $project->isNew()? '#': $project->toUrl('edit-form', ['absolute' => true])->toString(),
            ]);
          }
          else {
            $message = t('Site API was found, but the request was denied. Check the API Key in the <a href=":site_url">site</a>. The message was: %message', [
              ':site_url' => $this->isNew()? '#': $this->toUrl('edit-form', ['absolute' => true])->toString(),
              '%message' => $e->getMessage(),
            ]);
          }
          $this->addReason([
            '#type' => 'item',
            '#title' => t('Site API Access Denied.'),
            '#markup' => $message,
          ], 'site_api_403');
          $state = SiteEntity::SITE_ERROR;
          break;
      }
    }
    catch (\Exception $e) {
      $this->addReason([
        '#markup' => t('Something went wrong when saving data from @api: %error', [
          '%error' => $e->getMessage(),
          '@api' => Link::fromTextAndUrl($api_url, \Drupal\Core\Url::fromUri($api_url))->toString(),
        ])
      ], 'site_api_error');
      $state = SiteEntity::SITE_ERROR;
    }

    // @TODO: Running parent::getRemote() obliterates the received reasons data.

    // Load parent class properties like http_status.
    parent::getRemote();

    // Set state.
    if ($this->state->value < $state) {
      $this->state->setValue($state);
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

  /**
   * Return either the site->api_key or the project->api_key.
   * @return string
   */
  public function getApiKey(){
    $site = $this;
    if (!empty($site->get('api_key')->value)) {
      return $site->get('api_key')->value;
    }
    elseif (!empty($site->get('drupal_project')->first())) {
      $project = $site
        ->get('drupal_project')
        ->first()
        ->get('entity')
        ->getTarget()
      ;
      if (!empty($project->get('api_key')->value)) {
        return $project->get('api_key')->value;
      }
    }
  }

  /**
   * @return DrupalProject
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function drupalProject() {
    if (!empty($this->get('drupal_project')->first())) {
      return $this->get('drupal_project')->first()->get('entity')->getTarget()->getValue() ?? null;
    }
  }
}
