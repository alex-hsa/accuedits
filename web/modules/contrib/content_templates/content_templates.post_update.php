<?php

/**
 * @file
 * Contains post update hooks for content_templates module.
 */

/**
 * Set 'template' field from key value storage.
 */
function content_templates_post_update_10001(&$sandbox) {
  if (empty($sandbox['content_templates'])) {
    $sandbox['progress'] = 0;
    $sandbox['content_templates'] = \Drupal::keyValue('content_templates')->getAll();
    $sandbox['total_count'] = count($sandbox['content_templates']);
  }
  if (!empty($sandbox['content_templates'])) {
    $nids = array_keys($sandbox['content_templates']);
    $nids = array_slice($nids, 0, 25);
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
    foreach ($nodes as $node) {
      $node->set('template', $sandbox['content_templates'][$node->id()]);
      $node->save();
      unset($sandbox['content_templates'][$node->id()]);
    }
    $sandbox['progress'] += count($nids);
  }
  else {
    $sandbox['#finished'] = 1;
    return;
  }
  if ($sandbox['progress'] >= $sandbox['total_count']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = $sandbox['progress'] / $sandbox['total_count'];
  }
  return t("@current content items processed out of @total.", [
    '@current' => $sandbox['progress'],
    '@total' => $sandbox['total_count'],
  ]);
}

/**
 * Delete unused collection 'content_templates'.
 */
function content_templates_post_update_10002() {
  \Drupal::keyValue('content_templates')->deleteAll();
}

/**
 * Adjust "Content" view to have filter and column "Content template".
 */
function content_templates_post_update_10003() {
  content_templates_adjust_content_overview();
}
