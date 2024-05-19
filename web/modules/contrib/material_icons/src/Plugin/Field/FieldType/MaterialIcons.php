<?php

namespace Drupal\material_icons\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'link' field type.
 *
 * @FieldType(
 *   id = "material_icons",
 *   label = @Translation("Material Icons"),
 *   module = "material_icons",
 *   category = @Translation("Icons"),
 *   description = @Translation("A Material Design icon"),
 *   default_formatter = "material_icons",
 *   default_widget = "material_icons",
 * )
 */
class MaterialIcons extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      // Columns contains the values that the field will store.
      'columns' => [
        'icon' => [
          'type' => 'text',
          'size' => 'normal',
          'not null' => TRUE,
        ],
        'family' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => TRUE,
        ],
        'classes' => [
          'type' => 'text',
          'size' => 'normal',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['icon'] = DataDefinition::create('string')
      ->setLabel(t('Icon Name'))
      ->setDescription(t('The name of the icon'));
    $properties['family'] = DataDefinition::create('string')
      ->setLabel(t('Icon Style'))
      ->setDescription(t('The style of the icon'));
    $properties['classes'] = DataDefinition::create('string')
      ->setLabel(t('Icon Classes'))
      ->setDescription(t('The additional classes for the icon'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $icon_name = $this->get('icon')->getValue();
    return $icon_name === NULL || $icon_name === '';
  }

}
