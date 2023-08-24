<?php

namespace Drupal\site;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site\Entity\SiteEntity;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Base class for site_action plugins.
 */
abstract class SiteActionPluginBase extends PluginBase implements SiteActionInterface {

  /**
   * @var SiteEntityInterface The site entity being acted on.
   */
  protected SiteEntityInterface $site;

  public function __construct(array $configuration, $plugin_id, $plugin_definition)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setSite($configuration['site']);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return (string) $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function isOperation() {
    return (bool) !empty($this->pluginDefinition['site_entity_operation']);
  }

  /**
   * @param SiteEntityInterface $site
   * @return void
   */
  public function setSite(SiteEntityInterface $site) {
    $this->site = $site;
  }

  /**
   * @return SiteEntityInterface
   */
  public function getSite() {
    return $this->site;
  }

  /**
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * @return array
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Send form values to Site API.
    if ($this->getSite() && !$this->getSite()->isSelf()) {
      try {
        $return = $this->apiRequest();
        $form_state->set('action_api_return', $return);
      }
      catch (\Exception $e) {
        $form_state->setError($form, t('Site API returned an error: @message', [
          '@message' => $e->getMessage(),
        ]));
      }
    }
  }

  /**
   * POST to the Site API's action endpoint for this plugin.
   *
   * Use $this->apiResponse($response) to react to the response
   *
   * @return mixed
   */
  public function apiRequest() {

    // POST to Site API endpoint. Receive entity w/extra data.
    $base_url = $this->getSite()->api_url->value ?? $this->getSite()->site_uri->value;
    $site_api_url = $base_url . '/jsonapi/action/' . $this->getPluginId();
    $site_api_key = $this->getSite()->api_key->value ?? '';

    $client = new Client([
      'allow_redirects' => TRUE,
    ]);

    try {
      $response = $client->post($site_api_url, [
        'headers' => [
          'Accept' => 'application/vnd.api+json',
          'Content-type' => 'application/vnd.api+json',
          'api-key' => $site_api_key,
          'requester' => \Drupal::request()->getHost(),
        ],
        'json' => [
            'data' => $this->getSite()->toJsonApiArray(),
        ],
      ]);
      $site_remote = Json::decode($response->getBody()->getContents());
      return $site_remote;
    } catch (\Exception $e) {
      throw new BadRequestHttpException($e->getMessage());
    }
  }


  /**
   * @return array
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * @return string
   */
  public function formPageTitle() {
    return $this->label();
  }

  /**
   * Access check for this plugin. Checks for entity access 'action_ID' or global permission 'access all site actions'.
   *
   * @return bool
   */
  public function access() {
    $operation = "action_" . $this->getPluginId();
    if ($this->getSite()->access($operation)) {
      return true;
    }
    if (\Drupal::currentUser()->hasPermission('access all site actions')) {
      return true;
    }
  }

  /**
   * Alter the response to an API request for this action.
   *
   * @param array $response
   * @return void
   */
  public function apiResponse(array &$response) {
    $response['data']['links']['action_api'] = [
      'href' => \Drupal::request()->getUri(),
    ];
    $response['data']['site_action'][$this->getPluginId()] = $this->getPluginDefinition();
  }
}
