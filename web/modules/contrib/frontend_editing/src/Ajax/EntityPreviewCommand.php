<?php

namespace Drupal\frontend_editing\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Url;

/**
 * AJAX command for closing the side panel.
 *
 * This command is implemented in
 * Drupal.AjaxCommands.prototype.feEntityPreview()
 * defined in frontend_editing/js/frontend_editing.js.
 *
 * @ingroup ajax
 */
class EntityPreviewCommand implements CommandInterface {

  /**
   * A CSS selector string.
   *
   * If the command is a response to a request from an #ajax form element then
   * this value can be NULL.
   *
   * @var string
   */
  protected $selector;

  /**
   * The real UUID of the entity.
   *
   * Used in the case of a new entity.
   *
   * @var string|null
   */
  protected $realUuid;

  /**
   * The view mode to use for preview.
   *
   * @var mixed|string
   */
  protected $viewModeId;

  /**
   * Constructs a EntityPreviewCommand object.
   *
   * @param string|null $selector
   *   A CSS selector.
   * @param string|null $real_uuid
   *   The real UUID of the entity.
   * @param string $view_mode_id
   *   The view mode to use for preview.
   */
  public function __construct($selector = NULL, $real_uuid = NULL, $view_mode_id = 'default') {
    $this->selector = $selector;
    $this->realUuid = $real_uuid;
    $this->viewModeId = $view_mode_id;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $url = NULL;
    if ($this->realUuid) {
      $url = Url::fromRoute('preview.entity_preview', [
        'entity_preview' => $this->realUuid,
        'view_mode_id' => $this->viewModeId,
      ]);
    }
    return [
      'command' => 'feEntityPreview',
      'selector' => $this->selector,
      'realUuid' => $this->realUuid,
      'viewModeId' => $this->viewModeId,
      'url' => $url ? $url->toString() : '',
    ];
  }

}
