<?php

namespace Drupal\augmentor\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Augmentor field type.
 *
 * @FieldType(
 *   id = "field_augmentor_type",
 *   module = "augmentor",
 *   label = @Translation("Field Augmentor"),
 *   description = @Translation("Create a field using Augmentors."),
 *   default_widget = "augmentor_default_widget",
 *   default_formatter = "field_augmentor_formatter",
 *   cardinality = 1,
 *   category = "Augmentor",
 * )
 */
class AugmentorItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

}
