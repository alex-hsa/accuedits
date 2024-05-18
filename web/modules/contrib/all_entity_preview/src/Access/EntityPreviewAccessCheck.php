<?php

namespace Drupal\preview\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Access\AccessResultInterface;

/**
 * Determines access to node previews.
 *
 * @ingroup node_access
 */
class EntityPreviewAccessCheck implements AccessInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the entity preview page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_preview
   *   The node that is being previewed.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, ContentEntityInterface $entity_preview): AccessResultInterface {
    if ($entity_preview->isNew()) {
      $access_controller = $this->entityTypeManager->getAccessControlHandler($entity_preview->getEntityTypeId());
      return $access_controller->createAccess($entity_preview->bundle(), $account, [], TRUE);
    }
    else {
      return $entity_preview->access('update', $account, TRUE);
    }
  }

}
