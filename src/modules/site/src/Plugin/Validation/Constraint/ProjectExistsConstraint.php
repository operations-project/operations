<?php

namespace Drupal\site\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a Drupal Project Exists constraint.
 *
 * @Constraint(
 *   id = "ProjectExistsConstraint",
 *   label = @Translation("Verifies that a site has a project, and if not, create it."),
 * )
 * To apply this constraint, see https://www.drupal.org/docs/drupal-apis/entity-api/entity-validation-api/providing-a-custom-validation-constraint.
 */
class ProjectExistsConstraint extends Constraint {

  public $errorMessage = 'The error message.';

}
