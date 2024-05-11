<?php

namespace Drupal\augmentor\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * An augmentor field widget.
 *
 * @FieldWidget(
 *   id = "augmentor_file_widget",
 *   label = @Translation("Augmentor File Widget"),
 *   field_types = {
 *     "field_augmentor_type",
 *   },
 *   weight = "99",
 * )
 */
class AugmentorFileWidget extends AugmentorBaseWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $augmentor_field_name = 'execute_' . $this->fieldDefinition->getName();
    $form['#attached']['drupalSettings']['augmentor'][$augmentor_field_name]['data']['type'] = 'file';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    unset($element['action']);

    return $element;
  }

}
