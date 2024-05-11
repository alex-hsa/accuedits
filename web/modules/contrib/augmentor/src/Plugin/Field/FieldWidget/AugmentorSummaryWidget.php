<?php

namespace Drupal\augmentor\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * An augmentor field widget.
 *
 * @FieldWidget(
 *   id = "augmentor_summary_widget",
 *   label = @Translation("Augmentor Summary Widget"),
 *   field_types = {
 *     "field_augmentor_type",
 *   },
 *   weight = "99",
 * )
 */
class AugmentorSummaryWidget extends AugmentorBaseWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $augmentor_field_name = 'execute_' . $this->fieldDefinition->getName();
    $form['#attached']['drupalSettings']['augmentor'][$augmentor_field_name]['data']['type'] = 'summary';
    // Construct source fields from targets.
    $targets = $this->convertTargets($this->getSetting('targets'));
    $entity = $items->getEntity();
    $source_fields = [];
    foreach ($targets as $field_name) {
      $field_type = $entity->get($field_name)->getFieldDefinition()->getType();

      $source_fields[$field_name] = [
        $field_type => [
          'value' => '',
        ],
      ];
    }
    $form['#attached']['drupalSettings']['augmentor'][$augmentor_field_name]['data']['source_fields'] = $source_fields;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    unset($element['source_fields']);

    // @todo only allow fields with summaries.
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if ($augmentor = $this->getSetting('augmentor')) {
      $summary[] = $this->t('Augmentor name: @name', ['@name' => $augmentor]);
    }

    if ($target = $this->getSetting('targets')) {
      $summary[] = $this->t('Target fields: @target', ['@target' => $this->convertTargets($target, TRUE)]);
    }

    if ($action = $this->getSetting('action')) {
      $summary[] = $this->t('Action: @action', ['@action' => $action]);
    }

    if ($button_label = $this->getSetting('button_label')) {
      $summary[] = $this->t('Button label: @label', ['@label' => $button_label]);
    }

    return $summary;
  }

  /**
   * Converted nested array for sources to simple array.
   *
   * @param array $targets
   *   The configured array of targets.
   * @param bool $implode
   *   Whether or not to implode the return value.
   *
   * @return array|string
   *   The converted value.
   */
  public function convertTargets(array $targets, $implode = FALSE) {
    $converted = [];
    foreach ($targets as $target) {
      if (!empty($target['target_field'])) {
        $converted[] = $target['target_field'];
      }
    }
    return ($implode) ? implode(', ', $converted) : $converted;
  }

}
