<?php

/**
 * @file
 * Contains content_template.page.inc.
 *
 * Page callback for Content template entities.
 */

use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Prepares variables for content template templates.
 *
 * Default template: content_template.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_content_template(array &$variables) {
  // Fetch ContentTemplate Entity Object.
  $content_template = $variables['elements']['#content_template'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  $variables['content']['url'] = [
    '#plain_text' => Url::fromRoute('quick_node_clone.node.quick_clone',
      ['node' => $content_template->get('field_source')->target_id]
    )->toString(),
  ];

  $variables['content']['clone_url'] = Link::fromTextAndUrl(t('Create content from this template'), Url::fromRoute('quick_node_clone.node.quick_clone', [
    'node' => $content_template->get('field_source')->target_id
  ], ['attributes' => ['class' => 'clone-template', 'title' => t('Create content from this template')]]))->toRenderable();

  $variables['content']['edit_url'] = Link::fromTextAndUrl(t('Edit template'), Url::fromRoute('entity.node.edit_form', [
    'node' => $content_template->get('field_source')->target_id
  ], ['attributes' => ['class' => 'edit-template', 'title' => t('Edit template')]]))->toRenderable();
}
