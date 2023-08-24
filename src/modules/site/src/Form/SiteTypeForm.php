<?php

namespace Drupal\site\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\site\Entity\SiteType;

/**
 * Form handler for site type forms.
 */
class SiteTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    if (empty($this->entity)) {
      $this->setEntity(SiteType::load('default'));
    }

    $form = parent::form($form, $form_state);
    $type = $this->entity;

    $entity_type = $this->entity;
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit %label site type', ['%label' => $entity_type->label()]);
    }

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $entity_type->label(),
      '#description' => $this->t('The human-readable name of this site type.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\site\Entity\SiteType', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this site type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['labels'] = [
      '#type' => 'details',
      '#title' => $this->t('Labels'),
      '#group' => 'additional_settings',
    ];

    # @see EntityType

    $form['labels']['label_collection'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collection label'),
      '#default_value' => $type->get('label_collection'),
      '#required' => true,
      '#description' => $this->t("The human-readable label for a collection of entities of the site type. Used as the title on the @collection page.", [
        '@collection' => $this->entity->toLink(t(':type Collection', [
          ':type' => $this->entity->get('label'),
        ]), 'collection')->toString(),
      ]),
    ];
    $form['labels']['label_singular'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Singular label'),
      '#default_value' => $type->get('label_singular'),

      '#description' => $this->t("The indefinite singular name of the site type. Used on the @add page.", [
        '@add' => Link::createFromRoute(t('Add site'), 'entity.site.add_page')->toString(),
      ]),
      '#required' => true,
    ];
    $form['labels']['label_plural'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plural label'),
      '#default_value' => $type->get('label_plural'),
      '#description' => $this->t("The indefinite plural name of the site type."),
      '#required' => true,
    ];

    $form['labels']['label_count_singular'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Singulur Count'),
      '#default_value' => $type->get('label_count_singular'),
      '#description' => $this->t('Pattern to display count for a single site.'),
      '#required' => true,
    ];

    $form['labels']['label_count_plural'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plural Count'),
      '#default_value' => $type->get('label_count_plural'),
      '#description' => $this->t('Pattern to display count for mulitple sites.'),
      '#required' => true,
    ];

    $form['help'] = [
      '#type' => 'details',
      '#title' => $this->t('Help text'),
      '#group' => 'additional_settings',
    ];

    $form['help']['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => $this->t('This text will be displayed on the <em>Add site</em> page.'),
    ];

    $form['help']['help'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Explanation or submission guidelines'),
      '#default_value' => $type->getHelp(),
      '#description' => $this->t('This text will be displayed at the top of the page when creating or editing sites of this type.'),
    ];

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
    ];
    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save site type');
    $actions['delete']['#value'] = $this->t('Delete site type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_type = $this->entity;

    $entity_type->set('id', trim($entity_type->id()));
    $entity_type->set('label', trim($entity_type->label()));

    $status = $entity_type->save();

    $t_args = ['%name' => $entity_type->label()];
    if ($status == SAVED_UPDATED) {
      $message = $this->t('The site type %name has been updated.', $t_args);
    }
    elseif ($status == SAVED_NEW) {
      $message = $this->t('The site type %name has been added.', $t_args);
    }
    $this->messenger()->addStatus($message);

    $form_state->setRedirectUrl($entity_type->toUrl('collection'));
  }

}
