<?php

namespace Drupal\frontend_editing\Event;

use Drupal\Core\Access\AccessResult;

/**
 * Trait AccessResultTrait for getting and setting access result.
 *
 * @package Drupal\frontend_editing\Event
 */
trait AccessResultTrait {

  /**
   * The access result.
   *
   * @var \Drupal\Core\Access\AccessResult
   */
  protected $accessResult;

  /**
   * Gets access result.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The operation access result.
   */
  public function getAccessResult() {
    return $this->accessResult;
  }

  /**
   * Sets access result.
   *
   * @param \Drupal\Core\Access\AccessResult $accessResult
   *   The operation access result.
   */
  public function setAccessResult(AccessResult $accessResult) {
    $this->accessResult = $accessResult;
  }

}
