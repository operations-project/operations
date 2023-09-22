<?php

namespace Drupal\site\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\site\Entity\SiteDefinition;
use Drupal\site\Entity\SiteEntity;

/**
 * Form controller for the site entity edit forms.
 */
class SiteForm extends ContentEntityForm
{

  public function form(array $form, FormStateInterface $form_state)
  {
    $form = parent::form($form, $form_state);
    $form['revision']['#type'] = 'value';
    $form['revision']['#value'] = TRUE;

    // Node author information for administrators.
    $form['author'] = [
      '#type' => 'details',
      '#title' => $this->t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['node-form-author'],
      ],
      '#attached' => [
        'library' => ['node/drupal.node'],
      ],
      '#weight' => 90,
      '#optional' => TRUE,
    ];
    $form['created']['#group'] = 'author';
    $form['uid']['#group'] = 'author';
    $form['status']['#group'] = 'author';

    // Non-required fields.
    $form['site_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Site information'),
      '#description' => $this->t('Leave blank to automatically detect site information.'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['site-form-info'],
      ],
      '#attached' => [
//        'library' => ['node/drupal.node'],
      ],
      '#weight' => 10,
      '#optional' => TRUE,
    ];
    $form['hostname']['#group'] = 'site_info';
    $form['description']['#group'] = 'site_info';
    $form['site_title']['#group'] = 'site_info';

    $form['git_remote']['#group'] = 'site_info';
    $form['manager']['#group'] = 'site_info';

    $form['label']['widget'][0]['value']['#description'] .= ' ' . $this->t('If left blank, one will be generated from the URL.');

    if (empty($this->entity->hostname->value)) {
      $form['hostname']['widget'][0]['value']['#required'] = false;
    }

    // Site API information.
    $form['site_api'] = [
      '#type' => 'details',
      '#title' => $this->t('Site API'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['site-form-api'],
      ],
      '#attached' => [
//        'library' => ['node/drupal.node'],
      ],
      '#optional' => TRUE,
    ];

    $form['api_url']['#group'] = 'site_api';
    $form['api_key']['#group'] = 'site_api';

    if ($this->entity->bundle() == 'site_manager') {
      $form['site_api']['#title'] = t('Site Manager API');
      $form['site_api']['#description'] = t('To connect to this Site Manager, generate an API key and enter it below.');
    }

    return $form;
  }

  /**
   * @inheritdoc
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {

    // Set value of primary hostname if there is none.
    if (empty($form_state->getValue('hostname')[0]['value']) && !empty($form_state->getValue('site_uri')[0]['value'])) {
      $url = $form_state->getValue('site_uri')[0]['value'];
      $form_state->setValue(['hostname',0,'value'], parse_url($url, PHP_URL_HOST));
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = parent::validateForm($form, $form_state);

    // Set Form State UUID from entity validation.
    if (!empty($entity->get('uuid')->value) && $entity->get('uuid')->value != $form_state->getValue('uuid')) {
      $form_state->setValue('uuid', $entity->get('uuid')->value);
    }

    return $entity;
  }

  /**
   * Site Add Form
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function addForm(array $form, FormStateInterface $form_state) {

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $site = $this->entity;
    $insert = $site->isNew();

    try {
      $result = parent::save($form, $form_state);
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError(t('Unable to save site data. The error was: %error', [
        '%error' => $e->getMessage(),
      ]));
      return;
    }

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    if ($insert) {
      $this->messenger()->addStatus($this->t('New site %label has been created.', $message_arguments));
      $this->logger('site')->notice('Created new site %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The site %label has been updated.', $message_arguments));
      $this->logger('site')->notice('Updated site %label.', $logger_arguments);
    }

    if (\Drupal::routeMatch()->getRouteName() == 'site.edit') {
      $form_state->setRedirect('site.about');
    }
    else {
      $form_state->setRedirect('entity.site.canonical', [
        'site' => $this->entity->id(),
      ]);

//
//      // Redirect to site_definition canonical.
//      $form_state->setRedirect('entity.site_definition.canonical', [
//        'site_definition' => $this->entity->get('site_definition')->first()->get('entity')->getValue()->id(),
//      ]);
    }

    return $result;
  }

}
