<?php

namespace Drupal\frontend_editing;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;

/**
 * Defines ParagraphsHelperInterface Interface.
 *
 * @package src
 */
interface ParagraphsHelperInterface {

  /**
   * Checks if the paragraph can be moved up.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph to check.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function allowUp(ParagraphInterface $paragraph);

  /**
   * Checks if the paragraph can be moved down.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph to check.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function allowDown(ParagraphInterface $paragraph);

  /**
   * Moves the paragraph up or down.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph to move.
   * @param string $operation
   *   The operation to perform.
   *
   * @return bool
   *   TRUE if the paragraph was moved, FALSE otherwise.
   */
  public function move(ParagraphInterface $paragraph, $operation);

  /**
   * Get the redirect url.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   *
   * @return \Drupal\Core\Url
   *   The redirect url.
   */
  public function getRedirectUrl(ParagraphInterface $paragraph);

  /**
   * Get the root parent of a paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The root parent.
   */
  public function getParagraphRootParent(ParagraphInterface $paragraph);

  /**
   * Checks access for adding a paragraph of certain type to given parent.
   *
   * @param \Drupal\paragraphs\ParagraphsTypeInterface $paragraphs_type
   *   The paragraph type.
   * @param string $parent_type
   *   The paragraph parent type.
   * @param mixed $parent
   *   The id of the parent.
   * @param string $parent_field_name
   *   The field name of the parent.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function allowAddType(ParagraphsTypeInterface $paragraphs_type, $parent_type, $parent, $parent_field_name);

  /**
   * Checks access for adding a paragraph to given parent.
   *
   * @param string $parent_type
   *   The paragraph parent type.
   * @param mixed $parent
   *   The id of the parent.
   * @param string $parent_field_name
   *   The field name of the parent.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function allowAdd($parent_type, $parent, $parent_field_name);

}
