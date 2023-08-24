<?php

namespace Drupal\site\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site\Entity\SiteEntity;

/**
 * Plugin implementation of the 'State' formatter.
 *
 * @FieldFormatter(
 *   id = "site_state",
 *   label = @Translation("State Widget"),
 *   field_types = {
 *     "list_integer"
 *   }
 * )
 */
class StateFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_reason' => true,
      'reason_collapsible' => true,
      'reason_open' => true,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $elements['show_reasons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show reasons'),
      '#description' => $this->t('Display the reasons for the site state.'),
      '#default_value' => $this->getSetting('show_reasons'),
    ];
    $elements['reason_collapsible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapsable Fieldset'),
      '#description' => $this->t('Show reasons in a collapsible fieldset.'),
      '#default_value' => $this->getSetting('reason_collapsible'),
    ];

    $elements['reason_open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open'),
      '#description' => $this->t('When checked, the Reasons fieldset will be open.'),
      '#default_value' => $this->getSetting('reason_open'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Foo: @foo', ['@foo' => $this->getSetting('foo')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $field = $items->first();
    /** @var SiteEntity $site */
    $site = $field->getEntity();
    $renderer = \Drupal::service('renderer');


    $element = [
      '#type' => 'details',
      '#title' => $field->view(),
    ];

    if ($site->isDefaultRevision() && !empty($site->changed->value)) {
      $updated = $site->changed->view([
        'type'=> 'timestamp_ago',
        'label' => 'hidden',
        'settings' => [
          'granularity' => 1,
        ]
      ]);
    }
    else {
      $updated = $site->revision_timestamp->view([
        'type'=> 'timestamp_ago',
        'label' => 'hidden',
        'settings' => [
          'granularity' => 1,
        ]
      ]);
    }

    $element['#title']['ago'] = $updated;
    $element['#title']['ago']['#attributes']['class'][] = 'site-changed';

    $element['#title']['#prefix'] = "<em class='icon'></em>";
    $element['#title']['#suffix'] = t('');
    $element['#attributes']['class'][] = 'color-' . $site->stateClass();
    $element['#attributes']['class'][] = 'state-' . $site->stateClass();

    if ($this->getSetting('show_reason')) {
      $element[] = $site->reason->getValue();
      $element['#open'] = $this->getSetting('reason_open');
    }
    else {
      $element['#type'] = 'fieldset';
      $element['#title'] = $renderer->render($element['#title']);
    }

    $wrapper = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'site-state'
        ]
      ],
      $element
    ];
    return $wrapper;
  }
}
