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
    return $form;
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
