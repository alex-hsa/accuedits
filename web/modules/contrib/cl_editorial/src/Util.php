<?php

declare(strict_types=1);

namespace Drupal\cl_editorial;

use Drupal\sdc\Plugin\Component;

/**
 * Utility methods.
 */
class Util {

  /**
   * Checks if the input parameter is a prop or a slot.
   *
   * @param \Drupal\sdc\Plugin\Component $component
   *   The component.
   * @param string $input
   *   The name of the input parameter.
   *
   * @return ?string
   *   Either 'prop', 'slot', or NULL if not found.
   */
  public static function isPropOrSlot(Component $component, string $input): ?string {
    $mapped_on_prop = array_reduce(
      array_keys($component->metadata->schema['properties']),
      static fn(bool $is_prop, string $prop_name) => $is_prop || $prop_name === $input,
      FALSE
    );
    if ($mapped_on_prop) {
      return 'prop';
    }
    $mapped_on_slot = array_reduce(
      array_keys($component->metadata->slots),
      static fn(bool $is_slot, string $slot_name) => $is_slot || $slot_name === $input,
      FALSE
    );
    if ($mapped_on_slot) {
      return 'slot';
    }
    return NULL;
  }

}
