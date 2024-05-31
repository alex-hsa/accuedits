<?php

declare(strict_types=1);

namespace Drupal\sdc_display;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\sdc\Utilities;

/**
 * Utility methods.
 */
class Util {

  /**
   * Small helper function to determine if a field item is empty.
   *
   * @param mixed $field_item_list
   *   The field item list from the preprocessed variables.
   * @param int $delta
   *   The field delta.
   *
   * @return bool
   *   TRUE if the field is empty.
   */
  public static function fieldIsEmpty($field_item_list, int $delta): bool {
    if (!$field_item_list instanceof FieldItemListInterface) {
      return FALSE;
    }
    $field_item = NULL;
    try {
      $field_item = $field_item_list->get($delta);
    }
    catch (MissingDataException $e) {
      // Intentionally left blank.
    }
    if (!$field_item instanceof FieldItemInterface) {
      return FALSE;
    }
    return $field_item->isEmpty();
  }

  /**
   * Removes the HTML comments from an input string.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $input
   *   The input.
   *
   * @return string|\Drupal\Component\Render\MarkupInterface
   *   A safe string without HTML comments.
   */
  public static function removeHtmlComments($input): string|MarkupInterface {
    return Markup::create(trim(preg_replace(
      '/<!--(.|\s)*?-->\s*|\r|\n/',
      '',
      trim((string) $input)),
    ));
  }

  /**
   * Applies mappings to compute prop values.
   *
   * @param string[] $names
   *   The component.
   * @param array $static_mappings
   *   The static mappings.
   * @param array $dynamic_mappings
   *   The dynamic mappings.
   * @param array $element
   *   The render array.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   *
   * @return array
   *   The prop values.
   *
   * @throws \Exception
   */
  public static function computePropValues(array $names, array $static_mappings, array $dynamic_mappings, array $element, EntityInterface $entity, RendererInterface $renderer): array {
    // Iterate over all the component props and slots and populate their values
    // based o the dynamic mappings, falling back to the static mappings.
    $values = [];
    foreach ($names as $name) {
      $field_name = $dynamic_mappings[$name] ?? '';
      $skip_static_mapping =
        !empty($field_name)
        && $entity instanceof FieldableEntityInterface
        && $entity->hasField($field_name)
        && !$entity->get($field_name)->isEmpty();

      if (isset($element['#attached']['drupalSettings']['field_group']['sdc_display']['mode'])) {
        $display = \Drupal::entityTypeManager()
          ->getStorage('entity_view_display')
          ->load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.' . $element['#attached']['drupalSettings']['field_group']['sdc_display']['mode']);
        $skip_static_mapping = $skip_static_mapping || $display->getComponent($field_name);
      }

      if ($skip_static_mapping) {
        $values[$name] = $element[$field_name] ?? [];
        continue;
      }
      $fixed_value = $static_mappings[$name] ?? '';
      if (!empty($fixed_value)) {
        $values[$name] = $fixed_value;
      }
    }

    foreach ($values as $name => $value) {
      // If this is a render array, render it and remove the comments.
      if (!Utilities::isRenderArray($value)) {
        continue;
      }
      $values[$name] = static::removeHtmlComments(
        $renderer->render($values[$name])
      );
    }
    return $values;
  }

  /**
   * Applies mappings to compute prop values.
   *
   * @param string[] $names
   *   The component.
   * @param array $static_mappings
   *   The static mappings.
   * @param array $dynamic_mappings
   *   The dynamic mappings.
   * @param array $element
   *   The render array.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The prop values.
   */
  public static function computeSlotValues(array $names, array $static_mappings, array $dynamic_mappings, array $element, EntityInterface $entity): array {
    $values = [];
    foreach ($names as $name) {
      $field_names = array_keys(array_filter($dynamic_mappings[$name] ?? []));
      if (!empty($field_names)) {
        $values[$name] = array_map(
          static fn(string $field_name) => $element[$field_name],
          $field_names
        );
        // If, at least, one of the fields has data, then that's our value.
        $some_data = array_reduce(
          $field_names,
          static fn(bool $carry, string $field_name) =>
            $carry || !$entity->get($field_name)->isEmpty(),
          FALSE,
        );
        if ($some_data) {
          continue;
        }
      }
      $fixed_value = $static_mappings[$name] ?? [];
      if (!empty($fixed_value['value'])) {
        $values[$name] = [
          [
            '#type' => 'processed_text',
            '#text' => $fixed_value['value'],
            '#format' => $fixed_value['format'] ?? 'plain_text',
          ],
        ];
      }
    }
    return $values;
  }

}
