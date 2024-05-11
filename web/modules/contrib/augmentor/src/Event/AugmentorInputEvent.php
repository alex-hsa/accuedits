<?php

namespace Drupal\augmentor\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 *
 */
class AugmentorInputEvent extends Event {
  const ALTER = 'augmentor.input.alter';

  protected $input;

  /**
   *
   */
  public function __construct($input) {
    $this->input = $input;
  }

  /**
   *
   */
  public function getInput() {
    return $this->input;
  }

  /**
   *
   */
  public function setInput($input) {
    $this->input = $input;
  }

}
