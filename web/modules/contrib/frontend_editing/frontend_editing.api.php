<?php

/**
 * @file
 * Hooks provided by the Frontend Editing module.
 */

/**
 * Add fields to excluded list for frontend editing wrapper.
 *
 * Add only the full field names, e.g. node.article.field_example.
 *
 * @param array $fields
 *   The list of fields to be excluded.
 * @param array $context
 *   Context contains information about the entity type, bundle and field name.
 */
function hook_fe_field_wrapper_exclude_alter(array &$fields, array $context) {
  if ($context['entity_type'] == 'node' && $context['bundle'] == 'article') {
    $fields[] = 'node.article.field_example';
  }
}

/**
 * Alter the list of bundles allowed for frontend editing.
 *
 * Add only the full bundle names, e.g. node.article.
 *
 * @param array $bundles
 *   The list of bundles to be excluded.
 * @param array $context
 *   Context contains information about the entity type and bundle.
 */
function hook_fe_allowed_bundles_alter(array &$bundles, array $context) {
  if ($context['entity_type'] == 'node' && in_array('node.article', $bundles)) {
    unset($bundles['node.article']);
  }
  if ($context['entity_type'] == 'paragraph' && $context['view_display']->getMode() == 'ptoc') {
    $key = array_search($context['entity_type'] . '.' . $context['bundle'], $bundles);
    if ($key) {
      unset($bundles[$key]);
    }
  }
}
