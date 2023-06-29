<?php

namespace Drupal\site\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\site\Entity\SiteDefinition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Site event subscriber.
 */
class SiteSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::SAVE => ['onConfigSave'],
    ];
  }

  /**
   * Config save event handler.
   *
   * @param ConfigCrudEvent $event
   *   Response event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $site = SiteDefinition::load('self');
    if ($site->get('settings')['save_on_config']) {
      $site->saveEntity(t('Saved on config update for: :config', [
        ':config' => $event->getConfig()->getName(),
      ]));
    }
  }
}
