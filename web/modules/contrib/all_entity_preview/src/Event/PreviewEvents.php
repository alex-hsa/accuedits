<?php

namespace Drupal\preview\Event;

/**
 * Contains all events emitted in the preview module.
 */
final class PreviewEvents {

  /**
   * Name of the event fired when the preview back link is generated.
   *
   * @Event
   *
   * @see \Drupal\preview\Event\PreviewBackLink
   */
  const PREVIEW_BACK_LINK = 'preview.back_link';

}
