<?php

namespace Drupal\augmentor;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for augmentors.
 *
 * @see \Drupal\augmentor\Annotation\Augmentor
 * @see \Drupal\augmentor\AugmentorBase
 * @see \Drupal\augmentor\AugmentorManager
 * @see plugin_api
 */
interface AugmentorInterface extends PluginInspectionInterface, ConfigurableInterface, DependentPluginInterface, PluginFormInterface {

  /**
   * Returns a render array summarizing the config of augmentor.
   *
   * @return array
   *   A render array.
   */
  public function getSummary();

  /**
   * Returns the augmentor label.
   *
   * @return string
   *   The augmentor label.
   */
  public function label();

  /**
   * Returns the unique ID representing the augmentor.
   *
   * @return string
   *   The augmentor ID.
   */
  public function getUuid();

  /**
   * Sets the unique ID representing the augmentor.
   *
   * @param string $uuid
   *   The uuid for this augmentor.
   *
   * @return $this
   */
  public function setUuid($uuid);

  /**
   * Returns the weight of the augmentor.
   *
   * @return int|string
   *   Either the integer weight of the augmentor, or an empty string.
   */
  public function getWeight();

  /**
   * Sets the weight for this augmentor.
   *
   * @param int $weight
   *   The weight for this augmentor.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Sets the key API for this augmentor.
   *
   * @param string $key
   *   The key API for this augmentor.
   *
   * @return $this
   */
  public function setKey($key);

  /**
   * Returns the key API of the augmentor.
   *
   * @return string
   *   Key id to use for API authentication.
   */
  public function getKey();

  /**
   * Returns the key object.
   *
   * @return \Drupal\key\KeyInterface
   *   The augmentor key object.
   */
  public function getKeyObject();

  /**
   * Returns the key value.
   *
   * @return string
   *   The augmentor key value.
   */
  public function getKeyValue();

  /**
   * Returns the debug flag of the augmentor.
   *
   * @return bool
   *   The debug flag for this augmentor.
   */
  public function getDebug();

}
