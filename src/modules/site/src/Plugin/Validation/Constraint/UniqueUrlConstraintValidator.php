<?php

namespace Drupal\site\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueUrl constraint.
 */
class UniqueUrlConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {

    // getValue() returns an array of arrays, so this is easier.
    $uris = $entity->site_uri? explode(', ', $entity->site_uri->getString()): [];
    foreach ($uris as $i => $uri) {
      $uri = str_replace('https://', 'http://', $uri);
      $uri_https = str_replace('http://', 'https://', $uri);
      $existing_sites = \Drupal::entityTypeManager()
        ->getStorage('site')
        ->loadByProperties([
          'site_uri' => [
            $uri, $uri_https,
          ],
        ]);
      if ($existing_sites) {
        foreach ($existing_sites as $site) {
          if ($site->uuid() != $entity->uuid()) {
            if ($site->access('view')) {
              $this->context->buildViolation(t('The domain name @name is already in use.', [
                "@name" => $site->toLink($uri)->toString()
              ]))
                ->atPath('site_uri.' . $i)
                ->addViolation();
            }
            else {
              $this->context->buildViolation(t('The domain name is already in use.', [
                "@name" => $site->toLink()->toString()
              ]))
                ->atPath('site_uri.' . $i)
                ->addViolation();
            }
          }
        }
      }
    }

    if (in_array('http://default', $uris)) {
      $this->context->buildViolation(t('The URL http://default is not allowed.'))
        ->atPath('site_uri')
        ->addViolation();
    }

//      $this->context->buildViolation($constraint->errorMessage)
//        ->atPath('site_uri')
//        ->addViolation();

//    $existing_site = array_unshift($existing_sites);
//    if ($existing_site) {
//      $this->context->buildViolation($constraint->errorMessage)
//        ->atPath('site_uri')
//        ->addViolation();
//    }
  }
}
