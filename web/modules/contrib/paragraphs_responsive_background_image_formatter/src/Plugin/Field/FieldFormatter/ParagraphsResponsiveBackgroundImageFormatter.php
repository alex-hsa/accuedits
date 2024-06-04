<?php

namespace Drupal\paragraphs_responsive_background_image_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\responsive_bg_image_formatter\Plugin\Field\FieldFormatter\ResponsiveBgImageFormatter;

/**
 * Class ParagraphsResponsiveBackgroundImageFormatter.
 *
 * @FieldFormatter(
 *     id="paragraphs_responsive_background_image_formatter",
 *     label=@Translation("Paragraphs Responsive Background Image Formatter"),
 *     field_types={"image"}
 * )
 */
class ParagraphsResponsiveBackgroundImageFormatter extends ResponsiveBgImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $defaults = parent::defaultSettings();
    $defaults += [
      'dom_element_target' => 'paragraph',
    ];
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $settings = $this->getSettings();
    $element = parent::settingsForm($form, $form_state);
    $element['dom_element_target'] = [
      '#type' => 'radios',
      '#title' => $this->t('DOM element target'),
      '#description' => $this->t('Select the DOM element to target with the background image.'),
      '#options' => [
        'paragraph' => $this->t('Paragraph'),
        'field' => $this->t('Media field element'),
      ],
      '#weight' => 0,
      '#default_value' => $settings['dom_element_target'] ?? 'paragraph',
      '#required' => TRUE,
    ];
    unset($element['css_settings']['bg_image_selector']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();
    if (!empty($settings['image_style'])) {
      $summary[] = $this->t(
            'URL for image style: @style',
            [
              '@style' => $settings['image_style'],
            ]
        );
    }
    else {
      $summary[] = $this->t('Original image style');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $settings = $this->getSettings();
    /** @var \Drupal\paragraphs_responsive_background_image_formatter\SettingsService $settings_service */
    $settings_service = \Drupal::service('paragraphs_responsive_background_image_formatter.settings_service');
    // Get our settings from the global container and replace the defaults.
    $formatter_settings = $settings_service->getFormatterSettings();
    if (!empty($formatter_settings)) {
      $settings = array_replace_recursive($settings, $formatter_settings);
    }
    $this->setSettings($settings);
    return parent::viewElements($items, $langcode);
  }

}
