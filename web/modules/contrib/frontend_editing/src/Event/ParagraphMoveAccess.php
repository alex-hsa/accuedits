<?php

namespace Drupal\frontend_editing\Event;

use Drupal\paragraphs\ParagraphInterface;

/**
 * Defines the paragraph move access event.
 *
 * @package Drupal\frontend_editing\Event
 */
class ParagraphMoveAccess extends ParagraphAccessBase {

  /**
   * The operation.
   *
   * @var string
   */
  protected $operation;

  /**
   * ParagraphMoveAccess constructor.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   * @param string $operation
   *   The operation.
   */
  public function __construct(ParagraphInterface $paragraph, $operation) {
    parent::__construct($paragraph);
    $this->operation = $operation;
  }

  /**
   * Gets the operation up or down.
   *
   * @return string
   *   The operation to be performed.
   */
  public function getOperation() {
    return $this->operation;
  }

}
