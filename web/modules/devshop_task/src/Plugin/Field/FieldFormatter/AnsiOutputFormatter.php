<?php

namespace Drupal\devshop_task\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;

/**
 * Plugin implementation of the 'ANSI Output' formatter.
 *
 * @FieldFormatter(
 *   id = "devshop_task_ansi_output",
 *   label = @Translation("ANSI Output"),
 *   field_types = {
 *     "string",
 *     "string_long",
 *   }
 * )
 */
class AnsiOutputFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'foo' => 'bar',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $elements['foo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Foo'),
      '#default_value' => $this->getSetting('foo'),
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
    $element = [];
//    $converter = new AnsiToHtmlConverter();
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => $item->value,
      ];
    }

    return $element;
  }

}
