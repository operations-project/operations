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
    if ($site && !empty($site->get('settings')['save_on_config'])) {
      $data = $site->get('data');
      $data['config_changes'] = [
        'original' => $event->getConfig()->getOriginal(),
        'new' => $event->getConfig()->get(),
        'user' => \Drupal::currentUser()->getDisplayName(),
        'ip' => \Drupal::request()->getClientIp(),
        'url' => \Drupal::request()->getUri(),
      ];
      $site->set('data', $data);
      $entity = $site->saveEntity(t('Config ":config" updated at :url by ":user" (:ip)', [
        ':config' => $event->getConfig()->getName(),
        ':user' => \Drupal::currentUser()->getDisplayName(),
        ':url' => \Drupal::request()->getUri(),
        ':ip' => \Drupal::request()->getClientIp(),
      ]));

      \Drupal::messenger()->addStatus(t('Site report saved: @link', [
        '@link' => $entity->toLink()->toString(),
      ]));


    }
  }
}
