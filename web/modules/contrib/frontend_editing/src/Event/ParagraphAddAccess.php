<?php

namespace Drupal\frontend_editing\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;

/**
 * Defines the paragraph add access event.
 *
 * @package Drupal\frontend_editing\Event
 */
class ParagraphAddAccess extends Event {

  use AccessResultTrait;

  /**
   * The parent entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $parentEntity;

  /**
   * The parent field name.
   *
   * @var string
   */
  protected $parentFieldName;

  /**
   * The paragraphs type.
   *
   * @var \Drupal\paragraphs\ParagraphsTypeInterface
   */
  protected $paragraphsType;

  /**
   * ParagraphAddAccess constructor.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $parent_entity
   *   The parent entity.
   * @param string $parent_field_name
   *   The parent field name.
   * @param \Drupal\paragraphs\ParagraphsTypeInterface|null $paragraphs_type
   *   The paragraphs type (optional).
   */
  public function __construct(ContentEntityInterface $parent_entity, $parent_field_name, ParagraphsTypeInterface $paragraphs_type = NULL) {
    // By default, we allow the operation.
    $this->accessResult = AccessResult::allowed();
    $this->parentEntity = $parent_entity;
    $this->parentFieldName = $parent_field_name;
    $this->paragraphsType = $paragraphs_type;
  }

  /**
   * Gets the parent entity for paragraph to add.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The parent entity.
   */
  public function getParentEntity() {
    return $this->parentEntity;
  }

  /**
   * Gets the paragraphs type.
   *
   * @return \Drupal\paragraphs\ParagraphsTypeInterface|null
   *   The paragraphs type.
   */
  public function getParagraphsType() {
    return $this->paragraphsType;
  }

  /**
   * Gets the parent field name for paragraph to add.
   *
   * @return string
   *   The field name.
   */
  public function getParentFieldName() {
    return $this->parentFieldName;
  }

}
