<?php

namespace Drupal\site\Plugin\Validation\Constraint;

use Drupal\Component\Serialization\Json;
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

    $violations = [];

    // @TODO: "Refresh Data" button on Site Manager does not pick up remote values.

    // Check for values from POST/PATCH
    if (str_starts_with(\Drupal::routeMatch()->getRouteName(), 'jsonapi') && (\Drupal::request()->getMethod() == 'PATCH' || \Drupal::request()->getMethod() == 'POST')) {
      $request = \Drupal::request()->getContent();
      $response = Json::decode($request);
      $data = $response['data'];
      if (!empty($data['included'][0]['type']) && $data['included'][0]['type']== 'drupal_project--default') {
        $remote_project_data = $data['included'][0]['attributes'];
      }
    }

    $existing_project = $entity->drupalProject();

    // Save new project.
    if (
      $entity->hasField('drupal_project')
      && $entity->hasField('drupal_site_uuid')
      && !empty($entity->drupal_site_uuid->value)
      && empty($existing_project)
    ) {

      try {
        // Prepare default values from $entity (site)
        $values = [
          'drupal_site_uuid' => $entity->drupal_site_uuid->value,
          'drupal_site_name' => $entity->drupal_site_name->value,
          'git_remote' => $entity->git_remote->value ?? '',
          'canonical_url' => $entity->site_uri->value ?? '',
          'uid' => $entity->uid->target_id,
          'drupal_project_type' => 'default',
          'created' => time(),
          'changed' => time(),
          'revision_log_message' => t('Created Drupal Site entity automatically for :id', [':id' => $entity->drupal_site_uuid->value]),
        ];
        $drupal_project = DrupalProject::create($values);

        // Update values from remote data, if received.
        if (isset($remote_project_data)) {
          $drupal_project->updateFromJsonApiData($remote_project_data);
        }

        // save the project.
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
    // Update project.
    elseif (
      !empty($existing_project)
    ) {
      // @TODO: Should we update project when saving site locally?

      // Update project if received from JSON:API.
      if (isset($remote_project_data)) {
        $existing_project->updateFromJsonApiData($remote_project_data);
        $violations = $existing_project->validate();
        $existing_project->save();
      }
    }

    // Set the reference field drupal_project to the UUID.
    if (empty($entity->drupal_project->value) && !empty($entity->drupal_site_uuid->value)) {
      $entity->set('drupal_project', $entity->drupal_site_uuid->value);
    }

    return $violations;
  }
}
