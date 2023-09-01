<?php

namespace Drupal\site\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueUrl constraint.
 *
 * Site entities do not always need to have a unique URL.
 * The only time the URL is used to strictly identify a site is when
 * accessed via the Site API.
 *
 * When client sites post, they try to GET /jsonapi/self from the site manager
 * first to see if there is already a site entity with it's hostname.
 *
 * The Site Manager responds by looking up a site entity with that URL.
 * If there is more than one SiteEntity with that URL, Site Manager can lookup
 * using the API key as well.
 *
 * With that in mind, SiteEntities only need unique URLs for sites with API_URL
 */
class UniqueUrlConstraintValidator extends ConstraintValidator {

  const URI_EXISTS = 1;

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
                ->setCode(self::URI_EXISTS)
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
