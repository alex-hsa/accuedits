<?php

namespace Drupal\augmentor_chatgpt;

use Drupal\augmentor\AugmentorBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Orhanerday\OpenAi\OpenAi;

/**
 * ChatGPT Base augmentor plugin implementation.
 */
/**
 * Provides a base class for ChatGPT augmentors.
 *
 * @see \Drupal\augmentor\Annotation\Augmentor
 * @see \Drupal\augmentor\AugmentorInterface
 * @see \Drupal\augmentor\AugmentorManager
 * @see \Drupal\augmentor\AugmentorBase
 * @see plugin_api
 */
class ChatGptBase extends AugmentorBase implements ContainerFactoryPluginInterface {

  /**
   * Gets the OpenAI SDK API client.
   *
   * @return \Orhanerday\OpenAi\OpenAi
   *   The OpenAI SDK API client.
   */
  public function getClient(): OpenAi {

    // Only if not initialized yet.
    if (empty($this->client)) {
      $api_key = $this->getKeyValue();

      // Initialize API client.
      $this->client = new OpenAi($api_key);
    }
    return $this->client;
  }

}
