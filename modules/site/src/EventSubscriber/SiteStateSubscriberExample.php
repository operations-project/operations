<?php

namespace Drupal\site\EventSubscriber;

use Drupal\site\Entity\SiteDefinition;
use Drupal\site\Event\SiteGetState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Site event subscriber.
 */
class SiteStateSubscriberExample implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SiteGetState::GET_STATE => ['setState'],
    ];
  }

  /**
   *
   * Example method for determining site state.
   *
   * @param SiteGetState $event
   *   Response event.
   */
  public function setState(SiteGetState $event) {

    // Only set state if not SITE_OK.
    // $event->siteDefinition->set('state', SiteDefinition::SITE_WARN);

  }
}
