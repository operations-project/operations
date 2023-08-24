<?php

namespace Drupal\site\EventSubscriber;

use Drupal\Core\EventSubscriber\RedirectResponseSubscriber;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Site event subscriber.
 */
class SiteRedirectSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['redirectSelfCanonical'],
    ];
  }

  /**
   * If loading entity.site.canonical, and site is self, redirect to site.about.
   *
   * @param \Symfony\Component\HttpKernel\Event\ControllerEvent $event
   *   The ControllerEvent object.
   */
  public function redirectSelfCanonical(RequestEvent $event)
  {
    $request = $event->getRequest();
    $site = $request->get('site');
  }
}
