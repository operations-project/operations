<?php

namespace Drupal\site\Form;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\site\Entity\SiteDefinition;
use Drupal\site\Entity\SiteEntity;

/**
 * Provides a Site form.
 */
class SiteDefinitionEntitySaveForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_site_definition_entity_save';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

//    $site_definition = SiteDefinition::load('self');
//    $site = $site_definition->toEntity();
//    $site->set('revision_log', $form_state->getValue('revision_log'));

    $form['revision_log'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Log'),
      '#description' => $this->t('Enter a log message for this site record, if desired.'),
      '#required' => false,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Site Record'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $site_definition = SiteDefinition::load('self');
    $site = $site_definition->toEntity();
//    $site->set('revision_log', $form_state->getValue('revision_log'));

    try {
      $site->validate();
      $site->save();
    } catch (EntityStorageException $e) {
      $this->messenger()->addError($e->getMessage());
    }

    dsm($site);

    $this->messenger()->addStatus($this->t('A site record has been saved: @link', [
      '@link' => $site->toLink()->toString(),
    ]));
    $form_state->setRedirectUrl(Url::fromRoute('site.status'));
  }
}
