<?php

namespace Drupal\frontend_editing\Event;

/**
 * Defines events for the Frontend Editing module.
 */
final class FrontendEditingEvents {

  /**
   * Allows modules to change the access result for paragraph move action.
   *
   * @Event
   *
   * @see \Drupal\frontend_editing\Event\ParagraphMoveAccess
   */
  const FE_PARAGRAPH_MOVE_ACCESS = ParagraphMoveAccess::class;

  /**
   * Allows modules to change the access result for paragraph add action.
   *
   * @Event
   *
   * @see \Drupal\frontend_editing\Event\ParagraphAddAccess
   */
  const FE_PARAGRAPH_ADD_ACCESS = ParagraphAddAccess::class;

}
