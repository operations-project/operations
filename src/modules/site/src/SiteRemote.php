<?php

namespace Drupal\site;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jsonapi\Serializer\Serializer;
use Drupal\site\Annotation\SiteProperty;
use Drupal\site\Entity\Bundle\DrupalSiteBundle;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\SitePropertyPluginManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * A service to retrieve information about this site.
 */
class SiteRemote extends SiteSelf {

  /**
   * Set Properties, state and reason.
   * @param $entity
   * @return void
   */
  public function prepareEntity(&$entity) {
    $this->entity = $entity;

    /** @var SiteSelf $site_service */

    // Load site metadata
    // Unless we are saving a received site entity.
    // @TODO: Is there a better way to detect JSONAPI POSTed entity?
    if (\Drupal::routeMatch()->getRouteName() == 'jsonapi.site--drupal.individual' && \Drupal::request()->getMethod() == 'POST') {
      return $entity;
    }
    $this->load();

    // Set state and reason
    $entity->set('state', $this->getState());
    $entity->set('reason', $this->getReasons());
    $entity->set('data', $this->getData());

    // @TODO: Unhardcode

    // @TODO: Move to SiteRemote or HttpStatus?
    $data = $this->getData();
    if ($entity->hasField('php_version') && empty($entity->php_version->value) && isset($data['http_status']['headers']['X-Powered-By']) && strpos($data['http_status']['headers']['X-Powered-By'][0], 'PHP') === 0) {
      $entity->set('php_version', $data['http_status']['headers']['X-Powered-By'][0]);
    }
    if ($entity->hasField('drupal_version') && empty($entity->drupal_version->value) && isset($data['http_status']['headers']['X-Generator']) && strpos($data['http_status']['headers']['X-Generator'][0], 'Drupal') === 0) {
      $entity->set('drupal_version', $data['http_status']['headers']['X-Generator'][0]);
    }
    if ($entity->hasField('drupal_version') && empty($entity->drupal_version->value) && isset($data['http_status']['headers']['x-generator']) && strpos($data['http_status']['headers']['x-generator'][0], 'Drupal') === 0) {
      $entity->set('drupal_version', $data['http_status']['headers']['x-generator'][0]);
    }


    // Set all remote properties from plugins.
    foreach ($this->getProperties() as $name => $value) {
      if ($entity->hasField($name)) {
        $entity->set($name, $value);
      }
    }
    return $entity;
  }

  /**
   * @return array
   */
  public function load() {

    // Read site entity remotely.
    if ($this->entity) {


      // @TODO: Loop through remote site properties.
      // @TODO: Create a new plugin type for remote properties.
      $worst_plugin_state = SiteInterface::SITE_OK;
      $plugin_definitions = $this->siteProperty->getDefinitions();
      foreach ($plugin_definitions as $name => $plugin_definition) {
        if (!empty($plugin_definition['remote'])) {
          $plugin = $this->getProperty($plugin_definition['id']);

          $plugin->setContextValue('site', $this->entity);

          // Load property object into $this->property_plugins;
          $this->property_plugins[$name] = $plugin;
          $property_name = $plugin->name();
          $property_value = $plugin->value();
          $property_state = $plugin->state();
          $property_reasons = $plugin->reason();
          $property_data = $plugin->siteData();

          // Load property name and value.
          $this->properties[$property_name] = $property_value;
          $this->reasons[$property_name] = $property_reasons;
          $this->data[$property_name] = $property_data;

          // Set worst state.
          if ($property_state > $worst_plugin_state) {
            $worst_plugin_state = $property_state;
          }

        }
      }
      $this->state = $worst_plugin_state;

      // Gather remote site API data.
      if ($this->entity->bundle() == 'drupal') {
        $api_url = $this->entity->api_url->value;
        $api_url .= "/jsonapi/self";

        $api_key = $this->entity->api_key->value ?? '';

        $client = new Client([
          'base_url' => $api_url,
          'allow_redirects' => TRUE,
        ]);
        $payload['request'] = 'site';
        $options = [
          'headers' => [
            'Accept' => 'application/json',
            'api-key' => $api_key,
          ],
          'json' => $payload,
        ];
        try {
          $response = $client->get($api_url, $options);
          $received_content = $response->getBody()->getContents();
          $response_data = Json::decode($received_content);
          foreach ($this->entity->getFields() as $field_id => $field) {
            if (!empty($response_data['data']['attributes'][$field_id])) {

              // If JSONAPI->entity worked, this wouldn't be needed.
              switch ($field_id) {
                case 'revision_timestamp':
                case 'revision_log':
                  continue(2);

                case 'created':
                case 'changed':
                  $value = strtotime($response_data['data']['attributes'][$field_id]);
                  break;
                default:
                  $value = $response_data['data']['attributes'][$field_id];

              }
              $this->entity->set($field_id, $value);
            }
          }

          \Drupal::messenger()->addStatus(t('Loaded data from URL.'));


          $this->entity->setRevisionLogMessage(t('Received data from :url'));
        } catch (ClientException $e) {

          // This checks any drupal Site type for a Site API.
          // This doesn't necessarily warrant an error.
          switch($e->getCode()) {
            case 404:

              $this->reasons['site_api'] = [
                '#type' => 'item',
                '#title' => t('Site API not found.'),
                '#markup' => t('Install Site Module for enhanced functionality: <code>composer require drupal/site</code>. See @link for more information. The requested API URL was @api', [
                  '@link' => Link::fromTextAndUrl(t('drupal.org/project/site'), \Drupal\Core\Url::fromUri('https://www.drupal.org/project/site'))->toString(),
                  '@api' => Link::fromTextAndUrl($api_url, \Drupal\Core\Url::fromUri($api_url))->toString(),
                ])
              ];
              break;
            case 403:
              $this->reasons['site_api'] = [
                '#markup' => t('Site API Access Denied: %error', [
                  '%error' => $e->getMessage(),
                ]),
              ];
              $this->state = SiteEntity::SITE_ERROR;
              break;
          }
        }
        catch (\Exception $e) {
          $this->reasons['site_api'] = [
            '#markup' => t('Something went wrong when saving data: %error', [
              '%error' => $e->getMessage(),
            ])
          ];
          $this->state = SiteEntity::SITE_ERROR;
        }
      }
    }
    return $this;
  }
}
