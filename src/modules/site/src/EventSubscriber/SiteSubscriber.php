<?php

namespace Drupal\site\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\site\Entity\SiteDefinition;
use Drupal\site\Entity\SiteEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Site event subscriber.
 */
class SiteSubscriber implements EventSubscriberInterface {

  /**
   * @var SiteEntity
   */
  protected $site;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => ['onKernelResponse'],
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
    $site = $this->site ?? SiteDefinition::load('self');
    if ($site && !\Drupal::state()->get('site_config_events_disable')  && !empty($site->get('settings')['save_on_config'])) {
      $data = $site->get('data');
      $data['config_changes'][$event->getConfig()->getName()] = [
        'original' => $event->getConfig()->getOriginal(),
        'new' => $event->getConfig()->get(),
        'user' => \Drupal::currentUser()->getDisplayName(),
        'ip' => \Drupal::request()->getClientIp(),
        'url' => \Drupal::request()->getUri(),
      ];
      $site->set('data', $data);
    }
    $this->site = $site;
  }

  public function onKernelResponse(ResponseEvent $event) {
    if ($this->site) {
      if (is_array($this->site->get('data')['config_changes'])) {
        $entity = $this->site->saveEntity(t('Configs :config updated at :url by ":user" (:ip)', [
          ':config' => implode(', ', array_keys($this->site->get('data')['config_changes'])),
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
}
