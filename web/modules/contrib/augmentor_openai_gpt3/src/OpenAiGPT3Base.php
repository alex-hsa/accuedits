<?php

namespace Drupal\augmentor_openai_gpt3;

use Drupal\augmentor\AugmentorBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Orhanerday\OpenAi\OpenAi;

/**
 * OpenAI GPT3 augmentor plugin implementation.
 */
/**
 * Provides a base class for OpenAI GPT3 augmentors.
 *
 * @see \Drupal\augmentor\Annotation\Augmentor
 * @see \Drupal\augmentor\AugmentorInterface
 * @see \Drupal\augmentor\AugmentorManager
 * @see \Drupal\augmentor\AugmentorBase
 * @see plugin_api
 */
class OpenAiGPT3Base extends AugmentorBase implements ContainerFactoryPluginInterface {

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
