<?php

namespace Drupal\site\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\site\Entity\SiteDefinition;

/**
 * Site Definition form.
 *
 * @property \Drupal\site\SiteDefinitionInterface $entity
 */
class SiteDefinitionForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    if (!$this->entity->id()) {
      $this->setEntity(SiteDefinition::load('self'));
    }
    $form = parent::form($form, $form_state);
    $form['site_title'] = [
      '#type' => 'item',
      '#title' => $this->t('Site Title'),
      '#markup' => $this->entity->get('site_title'),
      '#description' => $this->t('The title of this site. Edit on <a href=":url">Basic site settings</a> page.', [
          ':url' => Url::fromRoute('system.site_information_settings')
              ->setOption('query', \Drupal::destination()->getAsArray())
              ->toString(),
      ]),
    ];
    $form['site_uuid'] = [
      '#type' => 'item',
      '#title' => $this->t('Site UUID'),
      '#markup' => $this->entity->get('site_uuid'),
      '#description' => $this->t('The UUID of this site.'),
    ];
    $form['site_uri'] = [
      '#type' => 'item',
      '#title' => $this->t('Site URI'),
      '#markup' => $this->entity->get('site_uri'),
      '#description' => $this->t('The URI of this site.'),
    ];
    $form['id'] = [
      '#value' => $this->entity->id(),
    ];

    // @TODO: If we allow creating these, uncomment the ID form.
//    $form['id'] = [
//      '#type' => 'machine_name',
//      '#default_value' => $this->entity->id(),
//      '#machine_name' => [
//        'exists' => '\Drupal\site\Entity\SiteDefinition::load',
//      ],
//      '#disabled' => !$this->entity->isNew(),
//    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->get('description'),
      '#description' => $this->t('Description of the site definition.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];

    $message = $result == SAVED_NEW
      ? $this->t('Created new site definition %label.', $message_args)
      : $this->t('Updated site definition %label.', $message_args);

    $this->messenger()->addStatus($message);

    $form_state->setRedirectUrl(Url::fromRoute('site.advanced'));
    return $result;
  }

}
