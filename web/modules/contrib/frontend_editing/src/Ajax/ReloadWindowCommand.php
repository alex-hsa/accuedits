<?php

namespace Drupal\frontend_editing\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class ReloadWindowCommand for reload ajax command.
 *
 * @package Drupal\frontend_editing\Ajax
 */
class ReloadWindowCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'feReloadWindow',
    ];
  }

}
