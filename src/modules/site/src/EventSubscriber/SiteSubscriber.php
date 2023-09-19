<?php

namespace Drupal\site\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Link;
use Drupal\site\Entity\SiteDefinition;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\Event\SitePreSaveEvent;
use Drupal\site\SiteSelf;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Site event subscriber.
 */
class SiteSubscriber implements EventSubscriberInterface {

  /**
   * @var SiteSelf
   */
  protected $site;

  /**
   * @var array
   */
  protected $config_changes;

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
    if (!\Drupal::state()->get('site_config_events_disable')  && \Drupal::config('site.settings')->get('save_on_config')) {
      $this->config_changes[$event->getConfig()->getName()] = [
        'original' => $event->getConfig()->getOriginal(),
        'new' => $event->getConfig()->get(),
        'user' => \Drupal::currentUser()->getDisplayName(),
        'ip' => \Drupal::request()->getClientIp(),
        'url' => \Drupal::request()->getUri(),
      ];
    }
  }

  public function onKernelResponse(ResponseEvent $event) {
    if ($this->config_changes) {
      try {
        $entity = \Drupal::service('site.self')->getEntity();

        // If entity author does not exist, an error is thrown. This happens in automated testing.
        // See https://git.drupalcode.org/project/operations/-/jobs/92135
        if (empty($entity->uid->entity)) {
          $entity->set('uid', \Drupal::currentUser()->id());
        }

        \Drupal::service('site.self')->setEntity($entity)->saveEntity(t('Configs :config updated at :url by ":user" (:ip)', [
          ':config' => implode(', ', array_keys($this->config_changes)),
          ':user' => \Drupal::currentUser() ? \Drupal::currentUser()->getDisplayName() : '(unknown)',
          ':url' => \Drupal::request()->getUri(),
          ':ip' => \Drupal::request()->getClientIp(),
        ]));
      }
      catch (\Exception $e) {
        \Drupal::messenger()->addError(t('Unable to save site report on configuration change: @message', [
          '@message' => $e->getMessage()
        ]));
        return;
      }

      \Drupal::messenger()->addStatus(t('Site report saved: @link', [
        '@link' => Link::createFromRoute( \Drupal::service('site.self')->getEntity()->label(), 'site.history')->toString(),
      ]));
    }
  }
}
