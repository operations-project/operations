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

    $site_definition = SiteDefinition::load('self');
    $existing_site = SiteEntity::load($site_definition->get('site_uuid'));

    $form['site_uuid'] = [
      '#type' => 'value',
      '#value' => $existing_site ? $existing_site->id(): null,
    ];
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
      '#value' => $this->t('Save Site Report'),
    ];
    $form['actions']['send'] = [
      '#title' => $this->t('Send Report'),
      '#description' => $this->t('If checked, a report will be sent to the configured reporting destinations.'),
      '#type' => 'checkbox',
      '#default_value' => true,
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
    $site_entity_new = $site_definition->toEntity();

    if ($form_state->getValue('site_uuid')) {
      $site = SiteEntity::load($form_state->getValue('site_uuid'));

      foreach ($site_entity_new as $name => $value) {
        $site->{$name} = $value;
      }
    }
    else {
      $site = $site_definition->toEntity();
    }

    try {
      $site->setNewRevision(TRUE);
      $site->setRevisionLogMessage($form_state->getValue('revision_log'));

      $site->validate();
      $site->save();

      if ($form_state->getValue('send')) {
        $site->send();
      }

    } catch (EntityStorageException $e) {
      $this->messenger()->addError($e->getMessage());
    }

    $this->messenger()->addStatus($this->t('A site record has been saved: @link', [
      '@link' => $site->toLink()->toString(),
    ]));
    $form_state->setRedirectUrl(Url::fromRoute('site.status'));
  }
}
