<?php

namespace Drupal\site\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the drupal project entity edit forms.
 */
class DrupalProjectForm extends ContentEntityForm {

  /**
   * @inheritdoc
   */
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

    // Add help
    $items = [];
    $items[] = t('The Drupal Site UUID uniquely identifies each site across environments.');
    $items[] = t('If your site already exists, you can retrieve the site UUID by running the command  <code>drush config:get system.site uuid</code>.');
    $items[] = t('Leave blank to generate a new site UUID.');

    $form['drupal_site_uuid']['widget'][0]['value']['#required'] = false;
    $form['drupal_site_uuid']['widget'][0]['value']['#description'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    return $form;
  }

  /**
   * Generates UUID for you.
   * @inheritdoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    if (empty($form_state->getValue('drupal_site_uuid')[0]['value'])) {
      $form_state->setValue('drupal_site_uuid', [[
        'value' => \Drupal::service('uuid')->generate()
      ]]);
    }
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New drupal project %label has been created.', $message_arguments));
        $this->logger('site')->notice('Created new drupal project %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The drupal project %label has been updated.', $message_arguments));
        $this->logger('site')->notice('Updated drupal project %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.drupal_project.canonical', ['drupal_project' => $entity->id()]);

    return $result;
  }

}
