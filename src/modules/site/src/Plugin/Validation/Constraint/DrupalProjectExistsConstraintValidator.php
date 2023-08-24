<?php

namespace Drupal\site\Plugin\Validation\Constraint;

use Drupal\site\Entity\DrupalProject;
use Drupal\site\Entity\SiteEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Drupal Project Exists. If not, tries to create one.
 *
 * If the entity has "drupal_project" entity reference field and
 * "drupal_site_uuid" string field, and there is no DrupalProject entity with
 * that id, create it.
 */
class DrupalProjectExistsConstraintValidator extends ConstraintValidator {

  /**
   * @var SiteEntity
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {

    if (
      $entity->hasField('drupal_project')
      && $entity->hasField('drupal_site_uuid')
      && !empty($entity->drupal_site_uuid->value)
      && empty(DrupalProject::load($entity->drupal_site_uuid->value))
    ) {

      try {
        $values = [
          'drupal_site_uuid' => $entity->drupal_site_uuid->value,
          'drupal_site_name' => $entity->drupal_site_name->value,
          'git_remote' => $entity->git_remote->value ?? '',
          'uid' => $entity->uid->target_id,
          'drupal_project_type' => 'default',
          'created' => time(),
          'changed' => time(),
          'revision_log_message' => t('Created Drupal Site entity automatically for :id', [':id' => $entity->drupal_site_uuid->value]),
        ];
        $drupal_project = DrupalProject::create($values);
        $drupal_project->save();

        \Drupal::logger('site')->notice(t('Created Drupal Site entity automatically for :id', [':id' => $entity->drupal_site_uuid->value]));

      }
      catch (\Exception $e) {
        $this->context->buildViolation(t('A Drupal project with the drupal_site_uuid of :uuid was not found and could not be created. The error message was: :error', [
          ':uuid' => $entity->drupal_site_uuid->value,
          ':error' => $e->getMessage(),
        ]))
          ->atPath('drupal_site_uuid')
          ->addViolation();
      }
    }

    // Set the reference field drupal_project to the UUID.
    if (empty($entity->drupal_project->value) && !empty($entity->drupal_site_uuid->value)) {
      $entity->set('drupal_project', $entity->drupal_site_uuid->value);
    }
  }
}
