<?php

namespace Drupal\site\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the exampleentity entity edit forms.
 */
class ExampleentityForm extends ContentEntityForm {

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
        $this->messenger()->addStatus($this->t('New exampleentity %label has been created.', $message_arguments));
        $this->logger('site')->notice('Created new exampleentity %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The exampleentity %label has been updated.', $message_arguments));
        $this->logger('site')->notice('Updated exampleentity %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.exampleentity.canonical', ['exampleentity' => $entity->id()]);

    return $result;
  }

}
