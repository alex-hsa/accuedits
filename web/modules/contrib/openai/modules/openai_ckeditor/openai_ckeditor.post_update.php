<?php

use Drupal\editor\Entity\Editor;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;

/**
 * Switch the deprecated Completion plugin for the new OpenAI plugin.
 */
function openai_ckeditor_post_update_completion_toolbar_item(&$sandbox = []) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $callback = function (Editor $editor) {
    if ($editor->getEditor() !== 'ckeditor5') {
      return FALSE;
    }

    $needs_update = FALSE;
    $settings = $editor->getSettings();

    if (is_array($settings['toolbar']['items']) && in_array('completion', $settings['toolbar']['items'], TRUE)) {
      $settings['toolbar']['items'] = str_replace('completion', 'openai', $settings['toolbar']['items']);
      $needs_update = TRUE;
    }

    if ($needs_update) {
      $editor->setSettings($settings);
    }

    return $needs_update;
  };

  $config_entity_updater->update($sandbox, 'editor', $callback);
}
