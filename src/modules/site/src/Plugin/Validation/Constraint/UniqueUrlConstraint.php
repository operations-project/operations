<?php

namespace Drupal\site\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides an UniqueUrl constraint.
 *
 * @Constraint(
 *   id = "SiteUniqueUrl",
 *   label = @Translation("Unique Url", context = "Validation"),
 * )
 *
 * @DCG
 * To apply this constraint, see https://www.drupal.org/docs/drupal-apis/entity-api/entity-validation-api/providing-a-custom-validation-constraint.
 */
class UniqueUrlConstraint extends Constraint {

  public $errorMessage = 'One of the entered domain names are already in use.';

}
