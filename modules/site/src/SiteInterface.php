<?php

namespace Drupal\site;

/**
 * Provides a standard interface that can be used with config and content entities.
 */
interface SiteInterface {

  /**
   * The site is not operating.
   */
  const SITE_ERROR = 2;

  /**
   * The site is operating normally.
   */
  const SITE_OK = 0;

  /**
   * The site is operating but with warnings.
   */
  const SITE_WARN = 1;

  /**
   * The site is operating and has information to present.
   */
  const SITE_INFO = -1;

  /**
   * Human-readable strings for state.
   *
   * @var string
   */
  const STATE_NAMES = [
      self::SITE_OK => 'OK',
      self::SITE_INFO => 'OK (info)',
      self::SITE_WARN => 'Warning',
      self::SITE_ERROR => 'Error',
  ];

}
