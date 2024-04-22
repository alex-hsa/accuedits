<?php

namespace Drupal\frontend_editing\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for closing the side panel.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.closeSidePanel()
 * defined in frontend_editing/js/frontend_editing.js.
 *
 * @ingroup ajax
 */
class CloseSidePanelCommand implements CommandInterface {

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
   * An entity id.
   *
   * In case of updating entity inside of entity reference (revisions) field
   * this will be the id of the host entity. This value can be NULL.
   *
   * @var mixed
   */
  protected $entityId;

  /**
   * An entity type.
   *
   * In case of updating entity inside of entity reference (revisions) field
   * this will be the entity type of the host entity. This value can be NULL.
   *
   * @var string
   */
  protected $entityType;

  /**
   * A field name.
   *
   * In case of updating entity inside of entity reference (revisions) field
   * this will be the name of the field of host entity. This value can be NULL.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Constructs a CloseSidePanelCommand object.
   *
   * @param string|null $selector
   *   A CSS selector.
   * @param mixed|null $entity_id
   *   An entity id.
   * @param string|null $entity_type
   *   An entity type.
   * @param string|null $field_name
   *   A field name.
   */
  public function __construct($selector = NULL, $entity_id = NULL, $entity_type = NULL, $field_name = NULL) {
    $this->selector = $selector;
    $this->entityId = $entity_id;
    $this->entityType = $entity_type;
    $this->fieldName = $field_name;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'closeSidePanel',
      'selector' => $this->selector,
      'entity_id' => $this->entityId,
      'entity_type' => $this->entityType,
      'field_name' => $this->fieldName,
    ];
  }

}
