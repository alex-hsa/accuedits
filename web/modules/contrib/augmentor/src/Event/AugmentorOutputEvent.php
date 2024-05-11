<?php

namespace Drupal\augmentor\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 *
 */
class AugmentorOutputEvent extends Event {
  const ALTER = 'augmentor.output.alter';

  protected $output;

  /**
   *
   */
  public function __construct($output) {
    $this->output = $output;
  }

  /**
   *
   */
  public function getOutput() {
    return $this->output;
  }

  /**
   *
   */
  public function setOutput($output) {
    $this->output = $output;
  }

}
