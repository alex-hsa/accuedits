<?php

namespace Drupal\openai_eca\Plugin\Action;

/**
 * Describes the OpenAI openai_eca_execute_embedding action.
 *
 * @Action(
 *   id = "openai_eca_execute_embedding",
 *   label = @Translation("OpenAI/ChatGPT Embedding"),
 *   description = @Translation("Generate a text embedding from an input.")
 * )
 */
class Embedding extends OpenAIActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $token_value = trim($this->tokenServices->getTokenData($this->configuration['token_input']));
    $response = $this->api->embedding($token_value);
    $this->tokenServices->addTokenData($this->configuration['token_result'], $response);
  }

}
