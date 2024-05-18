<?php

namespace Drupal\preview\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Event that is fired when the preview back link is generated.
 */
class PreviewBackLink extends Event {

  /**
   * The entity that is being previewed.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The back link.
   *
   * @var \Drupal\Core\Url|null
   */
  protected $backLink;

  /**
   * Constructs a new PreviewBackLink event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that is being previewed.
   * @param \Drupal\Core\Url|null $backLink
   *   The back link.
   */
  public function __construct(EntityInterface $entity, Url $backLink = NULL) {
    $this->entity = $entity;
    $this->backLink = $backLink;
  }

  /**
   * Gets the entity that is being previewed.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity object.
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Gets the back link.
   *
   * @return \Drupal\Core\Url|null
   *   The back link url.
   */
  public function getBackLink(): Url|NULL {
    return $this->backLink;
  }

  /**
   * Sets the back link.
   *
   * @param \Drupal\Core\Url $backLink
   *   The back link url.
   */
  public function setBackLink(Url $backLink): void {
    $this->backLink = $backLink;
  }

}
