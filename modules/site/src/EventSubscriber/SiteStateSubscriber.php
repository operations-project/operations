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
class SiteStateSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SiteGetState::GET_STATE => ['setState'],
    ];
  }

  /**
   * Site Get State handler
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
   */
  public function setState(SiteGetState $event) {
    $event->siteDefinition->set('state', SiteDefinition::SITE_WARN);
  }
}
