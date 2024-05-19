<?php

namespace Drupal\frontend_editing\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Access\AccessResult;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Defines the paragraph access base event.
 *
 * @package Drupal\frontend_editing\Event
 */
class ParagraphAccessBase extends Event {

  use AccessResultTrait;

  /**
   * The paragraph.
   *
   * @var \Drupal\paragraphs\ParagraphInterface
   */
  protected $paragraph;

  /**
   * ParagraphAccessBase constructor.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   */
  public function __construct(ParagraphInterface $paragraph) {
    $this->paragraph = $paragraph;
    // By default, we allow the operation.
    $this->accessResult = AccessResult::allowed();
  }

  /**
   * Gets paragraph that is being moved.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   The paragraph object.
   */
  public function getParagraph() {
    return $this->paragraph;
  }

}
