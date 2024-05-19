<?php

namespace Drupal\material_icons\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\material_icons\Traits\MaterialIconsSettings;

/**
 * Implementation of Material Icon formatter.
 *
 * @FieldFormatter(
 *   id = "material_icons",
 *   label = @Translation("Material Icons"),
 *   field_types = {
 *     "material_icons"
 *   }
 * )
 */
class MaterialIcons extends FormatterBase {

  use MaterialIconsSettings;

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $family = ($this->getFontFamilyClass($item->get('family')->getValue()));
      $element[$delta] = [
        '#theme' => 'material_icon',
        '#icon' => $item->get('icon')->getValue(),
        '#family' => $family,
        '#classes' => $item->get('classes')->getValue(),
      ];
    }
    return $element;
  }

}
