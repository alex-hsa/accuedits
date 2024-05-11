<?php

namespace Drupal\augmentor\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * An augmentor field widget.
 *
 * @FieldWidget(
 *   id = "augmentor_tags_widget",
 *   label = @Translation("Augmentor Tags Widget"),
 *   field_types = {
 *     "field_augmentor_type",
 *   }
 * )
 */
class AugmentorTagsWidget extends AugmentorBaseWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $augmentor_field_name = 'execute_' . $this->fieldDefinition->getName();
    $form['#attached']['drupalSettings']['augmentor'][$augmentor_field_name]['data']['type'] = 'tags';
    $form['#attached']['drupalSettings']['augmentor'][$augmentor_field_name]['data']['explode_separator'] = $this->getSetting('explode_separator');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'explode_separator' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['explode_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Explode separator'),
      '#default_value' => $this->getSetting('explode_separator'),
      '#size' => 10,
      '#description' => $this->t('Split augmentor response into an array using this separator.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($separator = $this->getSetting('explode_separator')) {
      $summary[] = $this->t('Explode separator: "@separator"', ['@separator' => $separator]);
    }

    return $summary;
  }

}
