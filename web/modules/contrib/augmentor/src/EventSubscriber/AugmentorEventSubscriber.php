<?php

namespace Drupal\augmentor\EventSubscriber;

use Drupal\augmentor\Event\AugmentorInputEvent;
use Drupal\augmentor\Event\AugmentorOutputEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 *
 */
class AugmentorEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AugmentorInputEvent::ALTER][] = ['onInputAlter', 0];
    $events[AugmentorOutputEvent::ALTER][] = ['onOutputAlter', 0];
    return $events;
  }

  /**
   * Modifies the input.
   *
   * @param \Drupal\augmentor\Event\AugmentorInputEvent $event
   *   The input event.
   */
  public function onInputAlter(AugmentorInputEvent $event) {
    // Modify the input as needed.
    $input = $event->getInput();
    // Perform operations...
    $event->setInput($input);
  }

  /**
   * Modifies the output.
   *
   * @param \Drupal\augmentor\Event\AugmentorOutputEvent $event
   *   The output event.
   */
  public function onOutputAlter(AugmentorOutputEvent $event) {
    // Modify the output as needed.
    $output = $event->getOutput();
    // Perform operations...
    $event->setOutput($output);
  }

}
