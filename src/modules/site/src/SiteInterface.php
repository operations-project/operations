<?php

namespace Drupal\site;

/**
 * Provides a standard interface that can be used with config and content entities.
 */
interface SiteInterface {

  /**
   * A process is running on the site.
   */
  const SITE_PROCESSING = 3;

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
      self::SITE_PROCESSING => 'Processing',
  ];

  /**
   * Human-readable strings for state.
   *
   * @var string
   */
  const STATE_IDS = [
      self::SITE_OK => 'ok',
      self::SITE_INFO => 'info',
      self::SITE_WARN => 'warning',
      self::SITE_ERROR => 'error',
      self::SITE_PROCESSING => 'processing',
  ];

  /**
   * Short string slugs
   *
   * @var string
   */
  const STATE_CLASSES = [
      self::SITE_OK => 'success',
      self::SITE_INFO => 'info',
      self::SITE_WARN => 'warning',
      self::SITE_ERROR => 'error',
      self::SITE_PROCESSING => 'processing',
  ];

  const STATE_ICONS = [
    self::SITE_OK => '✓',
    self::SITE_INFO => '⏼',
    self::SITE_WARN => '⚠',
    self::SITE_ERROR => '✗',
    self::SITE_PROCESSING => '☉',
  ];

}
