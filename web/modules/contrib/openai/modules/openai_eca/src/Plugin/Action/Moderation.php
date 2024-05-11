<?php

namespace Drupal\openai_eca\Plugin\Action;

/**
 * Describes the OpenAI openai_eca_execute_moderation action.
 *
 * @Action(
 *   id = "openai_eca_execute_moderation",
 *   label = @Translation("OpenAI/ChatGPT Moderation"),
 *   description = @Translation("Determine if a piece of text violates any OpenAI usage policies.")
 * )
 */
class Moderation extends OpenAIActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $token_value = trim($this->tokenServices->getTokenData($this->configuration['token_input']));
    $response = $this->api->moderation($token_value);
    $this->tokenServices->addTokenData($this->configuration['token_result'], $response);
  }

}
