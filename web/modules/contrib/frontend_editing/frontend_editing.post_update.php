<?php

/**
 * @file
 * Post update hooks for the frontend_editing module.
 */

/**
 * Grant 'add paragraphs' and 'move paragraphs' permissions.
 */
function frontend_editing_post_update_add_paragraph_permissions(&$sandbox) {
  // Get all roles.
  $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
  /** @var \Drupal\user\RoleInterface $role */
  foreach ($roles as $role) {
    // If role has permission to access frontend editing, allow to add and move
    // paragraphs for backwards compatibility.
    if ($role->hasPermission('access frontend editing')) {
      $role->grantPermission('add paragraphs');
      $role->grantPermission('move paragraphs');
      $role->save();
    }
  }
}
