<?php

namespace Drupal\content_templates\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining content template entities.
 *
 * @ingroup content_templates
 */
interface ContentTemplateInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Node template name.
   *
   * @return string
   *   Name of the Node template.
   */
  public function getName();

  /**
   * Sets the Node template name.
   *
   * @param string $name
   *   The Node template name.
   *
   * @return \Drupal\content_templates\Entity\ContentTemplateInterface
   *   The called Node template entity.
   */
  public function setName($name);

  /**
   * Gets the Node template creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Node template.
   */
  public function getCreatedTime();

  /**
   * Sets the Node template creation timestamp.
   *
   * @param int $timestamp
   *   The Node template creation timestamp.
   *
   * @return \Drupal\content_templates\Entity\ContentTemplateInterface
   *   The called Node template entity.
   */
  public function setCreatedTime($timestamp);

}
