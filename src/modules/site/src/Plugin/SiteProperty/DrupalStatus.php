<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\site\Entity\SiteDefinition;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\SitePropertyPluginBase;

/**
 * Plugin implementation of the site_property.
 *
 * @SiteProperty(
 *   id = "drupal_status",
 *   name = "drupal_status",
 *   site_bundles = {
 *     "Drupal\site\Entity\Bundle\DrupalSiteBundle"
 *   },
 *   hidden = true,
 *   label = @Translation("Drupal Status Report"),
 *   description = @Translation("The state of the Drupal Status Report.")
 * )
 */
class DrupalStatus extends SitePropertyPluginBase {

  public function state() {
    $state_factors = \Drupal::config('site.settings')->get('state_factors');
    if (in_array('system', $state_factors)) {

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

      $worst_severity = REQUIREMENT_INFO;
      $reasons = [];
      foreach ($requirements as $requirement_id => $requirement) {
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

          $reason_build = [
            '#type' => 'item',
            '#title' => t('Status report "@title" returned :thing.', [
              ':thing' => $type,
              '@title' => $requirement['title'],
              '@link' => Url::fromRoute('system.status')
                ->setAbsolute(TRUE)
                ->toString(),
            ]),
          ];

          if (!empty($requirement['description'])) {
            if (is_array($requirement['description'])) {
              $reason_build['description'] = $requirement['description'];
            }
            else {
              $reason_build['description'] = [
                '#markup' => $requirement['description'],
              ];
            }
          }

          $reasons[] = $reason_build;

          if ($requirement['severity'] > $worst_severity) {
            $worst_severity = $requirement['severity'];
          }
        }
      }
      $this->addReason($reasons);
      return $worst_severity;
    }
  }
}
