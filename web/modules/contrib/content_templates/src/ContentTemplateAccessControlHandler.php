<?php

namespace Drupal\content_templates;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the content template entity.
 *
 * @see \Drupal\content_templates\Entity\ContentTemplate.
 */
class ContentTemplateAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\content_templates\Entity\ContentTemplateInterface $entity */

    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished content template entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published content template entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit content template entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete content template entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add content template entities');
  }

}
