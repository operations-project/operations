<?php
/**
 * @file
 *
 * Helped by https://www.drupal.org/docs/creating-modules/subscribe-to-and-dispatch-events#s-my-first-drupal-8-event-and-event-dispatch
 */
namespace Drupal\site\Event;

use Drupal\site\Entity\SiteDefinition;
use Drupal\site_audit_report_entity\Entity\SiteAuditReport;
use Drupal\Component\EventDispatcher\Event;
use GuzzleHttp\Psr7\Response;

/**
 * Event that is fired when a Site is checking it's state.
 */
class SiteGetState extends Event {

  const GET_STATE = 'site_get_state';

  public SiteDefinition $siteDefinition;

  /**
   * Constructs the object.
   *
   * @param SiteDefinition $site_definition
   *   The report that was just sent.
   */
  public function __construct(SiteDefinition $site_definition) {
    $this->siteDefinition = $site_definition;
  }

}
