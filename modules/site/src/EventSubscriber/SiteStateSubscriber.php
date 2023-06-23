<?php

namespace Drupal\site\EventSubscriber;

use Drupal\Core\Url;
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

    // If site.settings.state_factors, load the report data and set the state.
      return [
          SiteGetState::GET_STATE => ['setStateFromSystemStatus'],
      ];
  }

  /**
   *
   * Example method for determining site state.
   *
   * @param SiteGetState $event
   *   Response event.
   */
  public function setStateFromSystemStatus(SiteGetState $event) {

    if (in_array('system', $event->siteDefinition->get('state_factors'))) {

      // If site.settings.state_factors, load the report data and set the state.

      // See system/system.admin.inc function system_status().
      // Load .install files.
      include_once DRUPAL_ROOT . '/core/includes/install.inc';
      drupal_load_updates();

      // Check run-time requirements and status information.
      $requirements = \Drupal::moduleHandler()->invokeAll('requirements', ['runtime']);
      usort($requirements, function ($a, $b) {
        if (!isset($a['weight'])) {
          if (!isset($b['weight'])) {
            return strcmp($a['title'], $b['title']);
          }
          return -$b['weight'];
        }
        return isset($b['weight']) ? $a['weight'] - $b['weight'] : $a['weight'];
      });

      $requirements_with_severity = [];
      foreach ($requirements as $key => $value) {
        if (isset($value['severity'])) {
          $requirements_with_severity[$key] = $value;
        }
      }
      $score_each = 100 / count($requirements_with_severity);

      $worst_severity = REQUIREMENT_INFO;
      $reasons [] = $event->siteDefinition->get('reason');

      foreach ($requirements as $requirement) {
        if (isset($requirement['severity'])) {
          if ($requirement['severity'] == REQUIREMENT_WARNING) {
            $type = t('a warning');
          }
          elseif ($requirement['severity'] == REQUIREMENT_ERROR) {
            $type = t('an error');
          }
          else {
            // @TODO: Add option to add INFO status entries?
            continue;
          }

          $reason = t('Status report "@title" returned :thing: See @link:', [
            ':thing' => $type,
            '@title' => $requirement['title'],
            '@link' => Url::fromRoute('system.status')
              ->setAbsolute(TRUE)
              ->toString(),
          ])->render();

          if (!empty($requirement['description'])) {
            $string = is_array($requirement['description'])?
              \Drupal::service('renderer')->renderRoot($requirement['description']):
              $requirement['description']
            ;
            $reason .= "<blockquote>$string</blockquote>";
          }

          $reasons[] = "<p>$reason</p>";

          if ($requirement['severity'] > $worst_severity) {
            $worst_severity = $requirement['severity'];
          }
        }
      }

      $event->siteDefinition->set('state', $worst_severity);
      $event->siteDefinition->set('reason', implode(" \n ", $reasons));
    }
  }
}
