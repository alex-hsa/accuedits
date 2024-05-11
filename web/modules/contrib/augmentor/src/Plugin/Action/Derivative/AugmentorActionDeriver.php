<?php

namespace Drupal\augmentor\Plugin\Action\Derivative;

use Drupal\Core\Action\Plugin\Action\Derivative\EntityActionDeriverBase;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an action deriver that supports Augmentor Operations.
 *
 * @see \Drupal\augmentor\Plugin\Action\AugmentorAction
 * @see \Drupal\augmentor\Plugin\Action\AugmentorActionMinimal
 */
class AugmentorActionDeriver extends EntityActionDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function isApplicable(EntityTypeInterface $entity_type) {
    return ($entity_type instanceof ContentEntityType && $entity_type->getBundleEntityType());
  }

}
