<?php

namespace Drupal\site\Plugin\Validation\Constraint;

use Drupal\Core\Link;
use Drupal\Core\Url;
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
 * first to see if there is an existing site entity with the same site_uri as the client site.
 *
 * The Site Manager calls loadBySiteUrl(), which, right now, only returns the first one found.
 *
 * With that in mind, SiteEntities only need unique URLs for sites with API_URL
 */
class UniqueUrlConstraintValidator extends ConstraintValidator {

  const URI_EXISTS = 1;

  /**
   * URLs must be unique across sites created by the same person.
   */
  const NO_DUPLICATE_USER_SITES = 'unique_urls_per_user';

  /**
   * URLs must be unique across drupal sites created by the same person.
   */
  const NO_DUPLICATE_USER_DRUPAL_SITES = 'unique_drupal_urls_per_user';

  /**
   * URLs must be unique across all drupal sites.
   */
  const NO_DUPLICATE_DRUPAL_SITES = 'unique_drupal_urls';

  /**
   * URLs must be unique across all site entities.
   */
  const NO_DUPLICATES = 'unique_urls';

  /**
   * {@inheritdoc}
   *
   * Block users from posting a SiteEntity with an existing URI.
   *
   * Drupal client sites use GET /jsonapi/self (with the selected API key) to find out the SiteEntity UUID so they can
   * PATCH or POST to the right URL: /jsonapi/site/drupal/UUID
   *
   * The Site Manager API looks up the SiteEntity with the URI that matches the client site making the API request.
   *
   * Therefor, only DrupalSiteBundle SiteEntities need unique URIs, and only for sites authored by the same user.
   *
   * This gives us the following config scenarios:
   *
   * 1. Block duplicate Drupal sites from author.
   * 2. Block duplicate Drupal sites for any author.
   * 3. Block all duplicate sites.
   *
   */
  public function validate($field, Constraint $constraint) {
    $entity = $field->getEntity();
    $duplicate_handling = \Drupal::config('site.settings')->get('duplicate_handling');
    $uris = [];
    foreach ($entity->site_uri->getValue() as $i => $item) {
      $uri = $item['value'];
      $uri = str_replace('https://', 'http://', $uri);
      $uri_https = str_replace('http://', 'https://', $uri);
      $uris[] = $uri_https;

      // Look up all sites with this URL.
      $properties = [
        'site_uri' => [
          $uri, $uri_https,
        ],
      ];

      // Filter based on duplicate handling.
      switch ($duplicate_handling) {
        // Look for any site by the author.
        case self::NO_DUPLICATE_USER_SITES:
          $properties['uid'] = [$entity->uid->value];
          break;

        // Look for duplicate Drupal sites by the author.
        case self::NO_DUPLICATE_USER_DRUPAL_SITES:
          $properties['site_type'] = ['drupal'];
          $properties['uid'] = [$entity->uid->value];
          break;

        // Look for all duplicate Drupal SiteEntities.
        case self::NO_DUPLICATE_DRUPAL_SITES:
          $properties['site_type'] = ['drupal'];
          break;
      }

      $existing_sites = \Drupal::entityTypeManager()
        ->getStorage('site')
        ->loadByProperties($properties);

      if ($existing_sites) {
        foreach ($existing_sites as $site) {
          if ($site->uuid() != $entity->uuid()) {
            if ($site->access('view')) {
              $this->context->buildViolation(t('The domain name @domain is already in use by the site @name', [
                "@domain" => Link::fromTextAndUrl($uri, Url::fromUri($uri))->toString(),
                "@name" => $site->toLink()->toString(),
              ]))
                ->atPath('site_uri.' . $i)
                ->setCode(self::URI_EXISTS)
                ->addViolation();
            }
            else {
              $this->context->buildViolation(t('The domain name @domain is unavailable. Try another.', [
                "@domain" => Link::fromTextAndUrl($uri, Url::fromUri($uri))->toString(),
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
