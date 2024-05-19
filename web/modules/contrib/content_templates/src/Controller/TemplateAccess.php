<?php

namespace Drupal\content_templates\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * Class TemplateAccess.
 *
 * @package Drupal\content_templates\Controller
 */
class TemplateAccess {

  /**
   * Checks if user has acces to the node.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User session.
   * @param int $node
   *   Node ID.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultForbidden
   *   Is access allowed or forbidden.
   */
  public function create(AccountInterface $account, $node) {
    $node = Node::load($node);
    if ($node->isNew() == FALSE) {
      return AccessResult::allowedIfHasPermissions($account, [
        'add content template entities',
        'clone ' . $node->bundle() . ' content',
      ]);
    }
    else {
      return AccessResult::forbidden();
    }
  }

}
