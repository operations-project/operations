<?php

namespace Drupal\site\Entity\Bundle;

use Drupal\Core\Entity\EntityConstraintViolationList;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\site\Entity\SiteEntity;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * A base bundle class for site entities.
 */
abstract class SiteBundle extends SiteEntity {
}
