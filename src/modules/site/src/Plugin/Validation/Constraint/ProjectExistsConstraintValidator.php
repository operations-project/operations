<?php

namespace Drupal\site\Plugin\Validation\Constraint;

use Drupal\Component\Serialization\Json;
use Drupal\site\Entity\DrupalProject;
use Drupal\site\Entity\Project;
use Drupal\site\Entity\ProjectType;
use Drupal\site\Entity\SiteEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Project Exists. If not, tries to create one.
 *
 * If the entity has "drupal_site_uuid" string field, and there is no DrupalProject entity with
 * that id, create it.
 */
class ProjectExistsConstraintValidator extends ConstraintValidator {

  /**
   * @var SiteEntity
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function validate($field, Constraint $constraint) {
    /** @var SiteEntity $entity */
    $entity = $field->getEntity();

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

    $existing_project = $entity->project();

    // Save new project.
    if (
      $entity->hasField('project')
      && $entity->hasField('drupal_site_uuid')
      && !empty($entity->drupal_site_uuid->value)
      && empty($existing_project)
    ) {

      try {
        // Prepare default values from $entity (site)
        // Create a project of the same type as the site. If project type does not exist, try "Default".
        if (empty(ProjectType::load($entity->bundle()))) {
          $project_type = 'default';
          if (empty(ProjectType::load('default'))) {
            $this->context->buildViolation(t('Unable to create a project automatically: There is no project type ":site" or ":type".', [
              ':site' => $entity->bundle(),
              ':type' => $project_type,
            ]))
              ->atPath('project')
              ->addViolation();
            return $this->context->getViolations();
          }
        }
        else {
          $project_type = $entity->bundle();
        }
        $values = [
          'drupal_site_uuid' => $entity->drupal_site_uuid->value,
          'label' => $entity->drupal_site_name->value,
          'git_remote' => $entity->git_remote->value ?? '',
          'canonical_url' => $entity->site_uri->value ?? '',
          'uid' => $entity->uid->target_id,
          'project_type' => $project_type,
          'created' => time(),
          'changed' => time(),
          'revision_log_message' => t('Created Drupal Project entity automatically for :id', [':id' => $entity->drupal_site_uuid->value]),
        ];
        $project = Project::create($values);

        // Update values from remote data, if received.
        if (isset($remote_project_data)) {
          $project->updateFromJsonApiData($remote_project_data);
        }

        // save the project.
        $project->save();

        \Drupal::logger('site')->notice(t('Created Project entity automatically for :id', [':id' => $entity->drupal_site_uuid->value]));
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

    // Set the reference field project to the Project ID.
    if (empty($entity->project->value) && !empty($project)) {
      $entity->set('project', $project->id());
    }

    return $violations;
  }
}
