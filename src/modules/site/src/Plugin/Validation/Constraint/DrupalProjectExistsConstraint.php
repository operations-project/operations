<?php

namespace Drupal\site\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a Drupal Project Exists constraint.
 *
 * @Constraint(
 *   id = "SiteDrupalProjectExists",
 *   label = @Translation("Drupal Project Exists", context = "Validation"),
 * )
 *
 * @DCG
 * To apply this constraint, see https://www.drupal.org/docs/drupal-apis/entity-api/entity-validation-api/providing-a-custom-validation-constraint.
 */
class DrupalProjectExistsConstraint extends Constraint {

  public $errorMessage = 'The error message.';

}
