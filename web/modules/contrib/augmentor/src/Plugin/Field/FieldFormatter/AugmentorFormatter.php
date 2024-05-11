<?php

namespace Drupal\augmentor\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides the Augmentor field formatter.
 *
 * @FieldFormatter(
 *   id = "field_augmentor_formatter",
 *   label = @Translation("Augmentor"),
 *   field_types = {
 *     "field_augmentor_type"
 *   }
 * )
 */
class AugmentorFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'debug' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['debug'] = [
      '#title' => $this->t('Enable debugging'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('debug'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $debug = $this->getSetting('debug');

    if (!empty($debug)) {
      $summary[] = $this->t('Debug: @debug', ['@debug' => $debug]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    return $element;
  }

}
