<?php

namespace Drupal\frontend_editing\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Ajax\ScrollTopCommand as ScrollTopCommandBase;

if (class_exists('\Drupal\Core\Ajax\ScrollTopCommand')) {
  /**
   * Core class is available from Drupal 10.1 and later.
   */
  class ScrollTopCommand extends ScrollTopCommandBase {

  }
}
else {

  /**
   * Provides an AJAX command for scrolling to the top of an element.
   *
   * This command is implemented in Drupal.AjaxCommands.prototype.scrollTop.
   */
  class ScrollTopCommand implements CommandInterface {

    /**
     * A CSS selector string.
     *
     * @var string
     */
    protected $selector;

    /**
     * Constructs a \Drupal\Core\Ajax\ScrollTopCommand object.
     *
     * @param string $selector
     *   A CSS selector.
     */
    public function __construct($selector) {
      $this->selector = $selector;
    }

    /**
     * {@inheritdoc}
     */
    public function render(): array {
      return [
        'command' => 'scrollTop',
        'selector' => $this->selector,
      ];
    }

  }
}
